<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\UserNotificationMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class SendEmailController extends Controller
{
     /**
     * Send emails to users (single or bulk by role)
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendEmails(Request $request)
    {
        try {
            // Validate request data
            $validator = Validator::make($request->all(), [
                'type' => 'required|string|in:single,bulk',
                'category' => 'required|string|in:general,promotional,notification,reminder,welcome,update',
                'subject' => 'required|string|min:3|max:255',
                'message' => 'required|string|min:10|max:10000',
                'template_info' => 'required|array',
                'template_info.category_label' => 'required|string|max:100',
                'template_info.is_template' => 'required|boolean',
                'template_info.format_as_html' => 'required|boolean',
                'template_info.include_branding' => 'required|boolean',
                'template_info.use_professional_layout' => 'required|boolean',
                // For single emails
                'user_id' => 'required_if:type,single|uuid|exists:users,uuid',
                // For bulk emails
                'recipient_type' => 'required_if:type,bulk|string|in:client,healthworker,all',
            ], [
                'type.required' => 'Email type is required.',
                'type.in' => 'Email type must be either single or bulk.',
                'category.required' => 'Email category is required.',
                'category.in' => 'Invalid email category.',
                'subject.required' => 'Email subject is required.',
                'subject.min' => 'Subject must be at least 3 characters.',
                'subject.max' => 'Subject must not exceed 255 characters.',
                'message.required' => 'Email message is required.',
                'message.min' => 'Message must be at least 10 characters.',
                'message.max' => 'Message must not exceed 10000 characters.',
                'user_id.required_if' => 'User ID is required for single emails.',
                'user_id.exists' => 'Invalid user selected.',
                'recipient_type.required_if' => 'Recipient type is required for bulk emails.',
                'recipient_type.in' => 'Invalid recipient type.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get authenticated admin user
            $admin = Auth::user();
            if (!$admin || $admin->role !== 'admin') {
                return response()->json([
                    'message' => 'Unauthorized. Only admins can send emails to users.'
                ], 403);
            }

            $emailData = $validator->validated();
            $recipients = collect();
            $failedEmails = collect();
            $successfulEmails = collect();

            // Get recipients based on type
            if ($emailData['type'] === 'single') {
                // Single email
                $user = User::where('uuid', $emailData['user_id'])->first();
                if ($user) {
                    $recipients->push($user);
                }
            } else {
                // Bulk email
                $query = User::query();
                
                switch ($emailData['recipient_type']) {
                    case 'client':
                        $query->where('role', 'client');
                        break;
                    case 'healthworker':
                        $query->where('role', 'healthworker');
                        break;
                    case 'all':
                        $query->whereIn('role', ['client', 'healthworker']);
                        break;
                }
                
                // Only get users with verified emails
                $recipients = $query->whereNotNull('email_verified_at')->get();
            }

            if ($recipients->isEmpty()) {
                return response()->json([
                    'message' => 'No valid recipients found for the specified criteria.'
                ], 404);
            }

            // Log email sending attempt
            Log::info('Admin email sending initiated', [
                'admin_id' => $admin->id,
                'admin_email' => $admin->email,
                'email_type' => $emailData['type'],
                'email_category' => $emailData['category'],
                'recipient_count' => $recipients->count(),
                'subject' => $emailData['subject']
            ]);

            // Send emails to each recipient
            foreach ($recipients as $recipient) {
                try {
                    Mail::to($recipient->email)->send(
                        new UserNotificationMail($recipient, $emailData)
                    );
                    
                    $successfulEmails->push([
                        'user_id' => $recipient->uuid,
                        'name' => $recipient->name,
                        'email' => $recipient->email,
                        'role' => $recipient->role
                    ]);

                } catch (\Exception $emailException) {
                    Log::error('Failed to send email to user', [
                        'admin_id' => $admin->id,
                        'recipient_id' => $recipient->uuid,
                        'recipient_email' => $recipient->email,
                        'error' => $emailException->getMessage()
                    ]);

                    $failedEmails->push([
                        'user_id' => $recipient->uuid,
                        'name' => $recipient->name,
                        'email' => $recipient->email,
                        'role' => $recipient->role,
                        'error' => $emailException->getMessage()
                    ]);
                }
            }

            // Prepare response data
            $totalRecipients = $recipients->count();
            $successCount = $successfulEmails->count();
            $failureCount = $failedEmails->count();

            // Log final results
            Log::info('Admin email sending completed', [
                'admin_id' => $admin->id,
                'total_recipients' => $totalRecipients,
                'successful_emails' => $successCount,
                'failed_emails' => $failureCount
            ]);

            // Determine response status and message
            if ($successCount === $totalRecipients) {
                // All emails sent successfully
                $status = 'Success';
                $message = $emailData['type'] === 'single' 
                    ? 'Email sent successfully.' 
                    : "All {$successCount} emails sent successfully.";
                $statusCode = 200;
            } elseif ($successCount > 0) {
                // Partial success
                $status = 'Partial Success';
                $message = "{$successCount} out of {$totalRecipients} emails sent successfully. {$failureCount} failed.";
                $statusCode = 207; // Multi-status
            } else {
                // All failed
                $status = 'Failed';
                $message = 'All email deliveries failed. Please check your email configuration.';
                $statusCode = 500;
            }

            return response()->json([
                'status' => $status,
                'message' => $message,
                'data' => [
                    'email_summary' => [
                        'type' => $emailData['type'],
                        'category' => $emailData['category'],
                        'subject' => $emailData['subject'],
                        'total_recipients' => $totalRecipients,
                        'successful_deliveries' => $successCount,
                        'failed_deliveries' => $failureCount,
                        'delivery_rate' => $totalRecipients > 0 ? round(($successCount / $totalRecipients) * 100, 2) : 0
                    ],
                    'successful_emails' => $successfulEmails->values(),
                    'failed_emails' => $failedEmails->values(),
                    'template_info' => $emailData['template_info']
                ]
            ], $statusCode);

        } catch (\Exception $e) {
            Log::error('Admin email sending failed', [
                'admin_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => app()->environment('production')
                    ? 'Something went wrong while sending emails.'
                    : $e->getMessage(),
                'error' => !app()->environment('production') ? $e->getMessage() : null
            ], 500);
        }
    }

}
