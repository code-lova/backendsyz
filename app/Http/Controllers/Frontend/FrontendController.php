<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Mail\ContactFormMail;
use App\Mail\ContactFormConfirmationMail;
use App\Mail\NewsletterSubscriptionMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class FrontendController extends Controller
{
    public function contactUsHandler(Request $request) {

        // we use a try and catch block to handle any errors
         // lets validate the data
         $validator = Validator::make($request->all(), [
            'fullname'              => 'required|string|max:255',
            'email'                 => 'required|email|max:255',
            'phone'                 => 'nullable|regex:/^\+?[0-9]{10,15}$/',
            'subject'               => 'required|string|max:255',
            'message'               => 'required|string|max:2000',
            'cf_turnstile_response' => 'required|string',
         ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get only the validated data
        $validatedData = $validator->validated();

        // Verify Cloudflare Turnstile
        $turnstileResponse = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => config('services.turnstile.secret_key'),
            'response' => $validatedData['cf_turnstile_response'],
            'remoteip' => $request->ip(),
        ]);

        if (!$turnstileResponse->json('success')) {
            Log::warning('Turnstile verification failed', [
                'email' => $validatedData['email'],
                'ip' => $request->ip(),
                'errors' => $turnstileResponse->json('error-codes'),
            ]);

            return response()->json([
                'message' => 'Security verification failed. Please try again.',
            ], 422);
        }
      
        try {

            // Prepare mail data
            $mailData = [
                'fullname' => $validatedData['fullname'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'] ?? null,
                'subject' => $validatedData['subject'],
                'message' => $validatedData['message'],
                'submitted_at' => now()->format('F j, Y g:i A'),
            ];

            // Get admin email from config
            $adminEmail = config('mail.admin_email') ?? env('ADMIN_EMAIL');

            // Send email to company support/admin
            Mail::to($adminEmail)->send(new ContactFormMail($mailData));

            // Send confirmation email to the user
            Mail::to($validatedData['email'])->send(new ContactFormConfirmationMail($mailData));

            // Log the contact form submission
            Log::info('Contact form submitted', [
                'fullname' => $validatedData['fullname'],
                'email' => $validatedData['email'],
                'subject' => $validatedData['subject'],
            ]);

            // Return success response
            return response()->json([
                'message' => 'Message received. We will get back to you shortly.',
                'success' => true,
            ], 200);

        }catch(\Exception $e) {
            // Log the error
            Log::error('Contact form submission failed', [
                'error' => $e->getMessage(),
                'email' => $validatedData['email'] ?? 'unknown',
            ]);

            return response()->json([
                'message' => app()->environment('production')
                    ? 'Something went wrong. Please try again later.'
                    : $e->getMessage(),
            ], 500);
        }

       
    }


    Public function subscribeNewsletter(Request $request) {
        // Validate the email
        $validator = Validator::make($request-> all(), [
            'email'     => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            // Check if email already exists
            $existingSubscriber = NewsletterSubscriber::where('email', $validatedData['email'])->first();

            if ($existingSubscriber) {
                // If already subscribed and active
                if ($existingSubscriber->isActive()) {
                    return response()->json([
                        'message' => 'You are already subscribed to our newsletter.',
                        'success' => true,
                        'already_subscribed' => true,
                    ], 200);
                }

                // Reactivate if previously unsubscribed
                $existingSubscriber->update([
                    'status' => 'active',
                    'subscribed_at' => now(),
                    'unsubscribed_at' => null,
                    'ip_address' => $request->ip(),
                ]);

                $subscriber = $existingSubscriber;
            } else {
                // Create new subscriber
                $subscriber = NewsletterSubscriber::create([
                    'email' => $validatedData['email'],
                    'status' => 'active',
                    'ip_address' => $request->ip(),
                    'source' => 'website',
                    'subscribed_at' => now(),
                ]);
            }

            // Prepare mail data
            $mailData = [
                'email' => $subscriber->email,
                'subscribed_at' => $subscriber->subscribed_at->format('F j, Y g:i A'),
                'unsubscribe_url' => config('app.url') . '/api/newsletter/unsubscribe/' . $subscriber->uuid,
            ];

            // Send welcome email to the subscriber
            Mail::to($subscriber->email)->send(new NewsletterSubscriptionMail($mailData));

            // Log the subscription
            Log::info('New newsletter subscription', [
                'email' => $subscriber->email,
                'uuid' => $subscriber->uuid,
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Newsletter subscription successful. Check your email for confirmation.',
                'success' => true,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Newsletter subscription failed', [
                'error' => $e->getMessage(),
                'email' => $validatedData['email'] ?? 'unknown',
            ]);

            return response()->json([
                'message' => app()->environment('production')
                    ? 'Something went wrong. Please try again later.'
                    : $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unsubscribe a user from the newsletter.
     *
     * @param string $uuid
     * @return \Illuminate\Http\Response
     */
    public function unsubscribeNewsletter(string $uuid)
    {
        try {
            // Find subscriber by UUID
            $subscriber = NewsletterSubscriber::where('uuid', $uuid)->first();

            if (!$subscriber) {
                return response()->view('emails.unsubscribe_result', [
                    'success' => false,
                    'message' => 'Invalid unsubscribe link. Subscriber not found.',
                ], 404);
            }

            // Check if already unsubscribed
            if (!$subscriber->isActive()) {
                return response()->view('emails.unsubscribe_result', [
                    'success' => true,
                    'message' => 'You have already been unsubscribed from our newsletter.',
                    'email' => $subscriber->email,
                ]);
            }

            // Unsubscribe the user
            $subscriber->unsubscribe();

            // Log the unsubscription
            Log::info('Newsletter unsubscription', [
                'email' => $subscriber->email,
                'uuid' => $subscriber->uuid,
            ]);

            return response()->view('emails.unsubscribe_result', [
                'success' => true,
                'message' => 'You have successfully unsubscribed from our newsletter.',
                'email' => $subscriber->email,
            ]);

        } catch (\Exception $e) {
            Log::error('Newsletter unsubscription failed', [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
            ]);

            return response()->view('emails.unsubscribe_result', [
                'success' => false,
                'message' => 'Something went wrong. Please try again later.',
            ], 500);
        }
    }

}
