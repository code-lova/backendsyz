<?php

namespace App\Http\Controllers\Client;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\clientSupportMessages;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use App\Models\clientSupportTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Services\ReferenceService;
use App\Mail\NewSupportTicketNotification;

class SupportTicketController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    public function createTicketMessage(Request $request, ReferenceService $referenceService)
    {
        $user = Auth::user();

        // Validate that the user exists and is active
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized. Please log in to create a support ticket.'
            ], 401);
        }

        // Verify user exists in database and is a client
        $validUser = User::where('uuid', $user->uuid)
            ->where('role', 'client')
            ->first();

        if (!$validUser) {
            return response()->json([
                'message' => 'Invalid user account. Only clients can create support tickets.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        try {
            DB::beginTransaction();

            //check if user has open ticket
            $openTicket = clientSupportTicket::where('user_uuid', $user->uuid)
                ->where('status', 'Open')
                ->first();

            if ($openTicket) {
                return response()->json([
                    'message' => 'You already have an open ticket. Please wait for a response or close your existing ticket before creating a new one.',
                    'existing_ticket' => [
                        'reference' => $openTicket->reference,
                        'subject' => $openTicket->subject,
                        'created_at' => $openTicket->created_at
                    ]
                ], 422);
            }

            $ticket = clientSupportTicket::create([
                'user_uuid' => $user->uuid,
                'reference' => $referenceService->getReference(
                    clientSupportTicket::class,
                    'reference',
                    'TICKET'
                ),
                'subject' => $data['subject'],
                'message' => $data['message'],
                'status' => 'Open', // Explicitly set status
            ]);

            // Send email notification to admin
            try {
                $adminEmail = config('mail.admin_email') ?? env('ADMIN_EMAIL');

                if ($adminEmail) {
                    $emailData = [
                        'uuid' => $ticket->uuid,
                        'reference' => $ticket->reference,
                        'subject' => $ticket->subject,
                        'message' => $ticket->message,
                        'status' => $ticket->status,
                        'client_name' => $user->name ?? 'Unknown Client',
                        'client_email' => $user->email ?? 'Unknown Email',
                        'created_at' => $ticket->created_at->format('F j, Y \a\t g:i A'),
                    ];

                    Mail::to($adminEmail)->send(new NewSupportTicketNotification($emailData));

                } else {
                    Log::warning('Admin email not configured - support ticket notification not sent', [
                        'ticket_uuid' => $ticket->uuid,
                        'ticket_reference' => $ticket->reference
                    ]);
                }

                // Notify admins about new client support message
                $this->notificationService->notifyAdminNewClientSupport(
                    $ticket->reference,
                    $user->name
                );

            } catch (\Exception $e) {
                // Log email error but don't fail the ticket creation
                Log::error('Failed to send support ticket notification email', [
                    'ticket_uuid' => $ticket->uuid,
                    'ticket_reference' => $ticket->reference,
                    'error' => $e->getMessage(),
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'Success',
                'message' => 'Ticket created successfully. You will receive a response shortly.',
                'data' => [
                    'id' => $ticket->uuid,
                    'reference' => $ticket->reference,
                    'subject' => $ticket->subject,
                    'message' => $ticket->message,
                    'status' => $ticket->status,
                    'created_at' => $ticket->created_at,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('create new ticket failed', [
                'user_id' => $user->id,
                'user_uuid' => $user->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => app()->environment('production') ? 'Something went wrong while creating the ticket.' : $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }



    //get the ticket message created by client
    public function getTicketAndMessages(Request $request)
    {
        $user = Auth::user();

        $tickets = ClientSupportTicket::with('messages')
            ->where('user_uuid', $user->uuid)
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform tickets to omit id and use uuid
        $transformed = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->uuid,
                'reference' => $ticket->reference,
                'subject' => $ticket->subject,
                'message' => $ticket->message,
                'status' => $ticket->status,
                'created_at' => $ticket->created_at,
                'messages' => $ticket->messages,
            ];
        });

        return response()->json([
            'status' => 'Success',
            'data' => $transformed,
        ], 200);
    }


    public function replyToTicket(Request $request, $id)
    {
        $user = Auth::user();

         // Validate that the user exists and is active
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized. Please log in to create a support ticket.'
            ], 401);
        }

        // Verify user exists in database and is a client
        $validUser = User::where('uuid', $user->uuid)
            ->where('role', 'client')
            ->first();

        if (!$validUser) {
            return response()->json([
                'message' => 'Invalid user account. Only clients can reply to support tickets.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        try {
            DB::beginTransaction();

            $ticket = clientSupportTicket::with('messages')
                ->where('user_uuid', $user->uuid)
                ->where('uuid', $id)
                ->first();

            if (!$ticket) {
                return response()->json([
                    'message' => 'Ticket not found.',
                ], 404);
            }

            //Lets get the role of the sender
            $senderType = $user->role === 'client' ? 'client' : 'admin';

            $reply = $ticket->messages()->create([
                'ticket_uuid' => $ticket->uuid,
                'sender_uuid' => $user->uuid,
                'sender_type' => $senderType,
                'message' => $data['message'],
            ]);

            // Notify admins about client reply to support ticket
            try {
                $this->notificationService->notifyAdminClientSupportReply(
                    $ticket->reference,
                    $user->name
                );
            } catch (\Exception $e) {
                // Log notification error but don't fail the reply creation
                Log::error('Failed to send admin notification for client support reply', [
                    'ticket_uuid' => $ticket->uuid,
                    'ticket_reference' => $ticket->reference,
                    'user_uuid' => $user->uuid,
                    'error' => $e->getMessage(),
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'Success',
                'message' => 'Reply sent successfully.',
                'data' => [
                    'id' => $reply->uuid,
                    'ticket_id' => $ticket->uuid,
                    'message' => $reply->message,
                    'created_at' => $reply->created_at,
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('reply to ticket failed', [
                'user_id' => $user->id,
                'ticket_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => app()->environment('production') ? 'Something went wrong while replying to the ticket.' : $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }


}
