<?php

namespace App\Http\Controllers\Healthworker;

use App\Http\Controllers\Controller;
use App\Mail\NewSupportMessage;
use App\Mail\SupportTicketConfirmationMail;
use App\Models\SupportMessage;
use App\Services\ReferenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class SupportController extends Controller
{
    public function createSupportMessage(Request $request, ReferenceService $referenceService){
        try{

            $user = Auth::user();

            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'subject' => 'required|string|max:255',
                'message' => 'required|string|min:10|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $checkForPendingMessage = SupportMessage::where('user_uuid', $user->uuid)
                ->where('status', 'Pending')
                ->first();
            if($checkForPendingMessage){
                return response()->json([
                    'message' => 'You already have an open support ticket.'
                ], 429);
            }

            $supportMessage = new SupportMessage();
            $supportMessage->user_uuid = $user->uuid; // If authenticated
            $supportMessage->reference = $referenceService->getReference(
                SupportMessage::class,
                'reference',
                'SUPPORT'
            );
            $supportMessage->subject = $request->subject;
            $supportMessage->message = $request->message;
            $supportMessage->status = 'Pending';
            $supportMessage->save();

            // Prepare ticket data for emails
            $ticketData = [
                'reference' => $supportMessage->reference,
                'subject' => $supportMessage->subject,
                'message' => $supportMessage->message,
                'status' => $supportMessage->status,
            ];

            // Send confirmation email to health worker
            try {
                Mail::to($user->email)->send(new SupportTicketConfirmationMail($ticketData, $user));
                
               
            } catch (\Exception $e) {
                Log::error('Failed to send support ticket confirmation email to health worker', [
                    'user_uuid' => $user->uuid,
                    'user_email' => $user->email,
                    'support_reference' => $supportMessage->reference,
                    'error' => $e->getMessage(),
                ]);
                // Do not fail support creation if email fails
            }

            // Send email to admin with support message content
            try {
                $adminEmail = config('mail.admin_email') ?? env('ADMIN_EMAIL');
                
                if (!$adminEmail) {
                    Log::warning('Admin email not configured for support ticket notifications', [
                        'user_uuid' => $user->uuid,
                        'support_reference' => $supportMessage->reference
                    ]);
                } else {
                    $mailData = [
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'subject' => $supportMessage->subject,
                        'support_message' => $supportMessage->message,
                        'reference' => $supportMessage->reference,
                    ];
                    
                    Mail::to($adminEmail)->send(new NewSupportMessage($mailData));
                    
                    Log::info('Support ticket email sent successfully to admin', [
                        'user_uuid' => $user->uuid,
                        'admin_email' => $adminEmail,
                        'support_reference' => $supportMessage->reference
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to send support ticket email', [
                    'user_uuid' => $user->uuid,
                    'admin_email' => $adminEmail ?? 'not found',
                    'support_reference' => $supportMessage->reference ?? 'not generated',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Do not fail support creation if email fails
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Support ticket submitted successfully. A confirmation email has been sent to you.',
                'data' => [
                    'reference' => $supportMessage->reference,
                    'status' => $supportMessage->status,
                    'created_at' => $supportMessage->created_at,
                ]
            ], 201);

        }catch(\Exception $e){
            DB::rollBack();
            Log::error('support update failed', [
                'user_uuid' => $user->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $message = app()->environment('production')
            ? 'Something Went Wrong'
            : $e->getMessage();


            return response()->json([
                'message' => $message,
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function getSupportMessages(Request $request) {
        try{

            $user = Auth::user();

            $supportDetails = SupportMessage::where('user_uuid', $user->uuid)->latest()->get();

            if ($supportDetails->isEmpty()) {
                return response()->json(['message' => 'No Support ticket found'], 404);
            }

             // Format all messages
            $formattedMessages = $supportDetails->map(function ($msg) {
                return [
                    'id' => $msg->uuid,
                    'subject' => $msg->subject,
                    'message' => $msg->message,
                    'status' => $msg->status,
                    'reference' => $msg->reference,
                    'created_at' => $msg->created_at,
                ];
            });

            return response()->json([
                'tickets' => $formattedMessages
            ]);

        }catch(\Exception $e){
            $message = app()->environment('production')
            ? 'Something Went Wrong'
            : $e->getMessage();

            return response()->json([
                'message' => $message,
                'error' => $e->getMessage()
            ], 500);
        }
    }


}
