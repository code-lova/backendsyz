<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\clientSupportTicket;
use App\Models\clientSupportMessages;
use App\Models\User;
use App\Mail\AdminReplyNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TicketSupport extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * List all support tickets with client details and messages.
     *
     * Retrieves all support tickets created by clients along with their messages,
     * validates client existence, and provides comprehensive ticket information.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listSupportTickets(Request $request)
    {
        try {
            // Validate request parameters
            $validator = Validator::make($request->all(), [
                'status' => 'nullable|string|in:Open,Closed',
                'sort' => 'nullable|string|in:newest,oldest,subject_asc,subject_desc,status_asc,status_desc',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'search' => 'nullable|string|max:255', // Search by reference, subject, or client name
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Build the query for support tickets
            $query = clientSupportTicket::with([
                'user:uuid,name,email,phone,image,country,created_at', // Client details
                'messages' => function($query) {
                    $query->with('sender:uuid,name,email,role')
                          ->orderBy('created_at', 'asc'); // Order messages chronologically
                }
            ]);

            // Apply search filter
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('reference', 'like', "%$search%")
                      ->orWhere('subject', 'like', "%$search%")
                      ->orWhereHas('user', function($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%$search%")
                                   ->orWhere('email', 'like', "%$search%");
                      });
                });
            }

            // Apply status filter
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Apply sorting
            switch ($request->sort) {
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'subject_asc':
                    $query->orderBy('subject', 'asc');
                    break;
                case 'subject_desc':
                    $query->orderBy('subject', 'desc');
                    break;
                case 'status_asc':
                    $query->orderBy('status', 'asc');
                    break;
                case 'status_desc':
                    $query->orderBy('status', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc'); // Default: newest first
                    break;
            }

            // Get pagination parameters
            $perPage = $request->per_page ?? 15;
            $page = $request->page ?? 1;

            // Execute the paginated query
            $tickets = $query->paginate($perPage, ['*'], 'page', $page);

            // Process tickets to validate clients and add additional information
            $processedTickets = $tickets->getCollection()->map(function ($ticket) {
                // Check if client is valid (exists and account is active)
                $isValidClient = $ticket->user && $ticket->user->exists;

                // Add additional computed fields
                $ticket->is_valid_client = $isValidClient;
                $ticket->client_status = $isValidClient ? 'active' : 'invalid';
                $ticket->total_messages = $ticket->messages->count();
                $ticket->last_message_at = $ticket->messages->last()?->created_at;
                $ticket->last_sender = $ticket->messages->last()?->sender;

                // Add message statistics
                $adminMessages = $ticket->messages->where('sender.role', 'admin')->count();
                $clientMessages = $ticket->messages->where('sender.role', 'client')->count();

                $ticket->admin_replies = $adminMessages;
                $ticket->client_replies = $clientMessages;

                // Determine who needs to respond next
                $lastMessage = $ticket->messages->last();
                if ($lastMessage && $lastMessage->sender) {
                    // If last message is from client, admin needs to respond
                    // If last message is from admin, client needs to respond
                    $ticket->awaiting_response = $lastMessage->sender->role === 'client' ? 'admin' : 'client';
                } else {
                    // If no messages exist (shouldn't happen) or no sender, default to admin
                    $ticket->awaiting_response = 'admin';
                }

                return $ticket;
            });

            // Update the collection in the paginator
            $tickets->setCollection($processedTickets);

            if ($tickets->isEmpty()) {
                return response()->json([
                    'message' => 'No support tickets found.',
                    'data' => [],
                    'meta' => [
                        'total' => 0,
                        'per_page' => $perPage,
                        'current_page' => $page,
                        'last_page' => 1,
                        'from' => null,
                        'to' => null
                    ]
                ], 200);
            }

            // Get summary statistics
            $totalTickets = clientSupportTicket::count();
            $openTickets = clientSupportTicket::where('status', 'Open')->count();
            $closedTickets = clientSupportTicket::where('status', 'Closed')->count();

            return response()->json([
                'status' => 'Success',
                'message' => 'Support tickets retrieved successfully.',
                'data' => $tickets->items(),
                'meta' => [
                    'total' => $tickets->total(),
                    'per_page' => $tickets->perPage(),
                    'current_page' => $tickets->currentPage(),
                    'last_page' => $tickets->lastPage(),
                    'from' => $tickets->firstItem(),
                    'to' => $tickets->lastItem(),
                    'has_more_pages' => $tickets->hasMorePages()
                ],
                'summary' => [
                    'total_tickets' => $totalTickets,
                    'open_tickets' => $openTickets,
                    'closed_tickets' => $closedTickets
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Support tickets retrieval failed', [
                'admin_request' => true,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => app()->environment('production')
                    ? 'Something went wrong while retrieving support tickets.'
                    : $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reply to a client support ticket as an admin.
     *
     * Allows admin to reply to an existing support ticket, validates ticket existence,
     * ensures it's open for replies, and creates a new message record.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $ticketUuid The UUID of the ticket to reply to
     * @return \Illuminate\Http\JsonResponse
     */
    public function replyToTicket(Request $request, $ticketUuid)
    {
        try {
            // Validate UUID format
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $ticketUuid)) {
                return response()->json([
                    'message' => 'Invalid ticket ID format.'
                ], 400);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'message' => 'required|string|min:10|max:2000',
                'close_ticket' => 'nullable|boolean', // Option to close ticket after reply
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            // Get authenticated admin user
            $admin = Auth::user();
            if (!$admin || $admin->role !== 'admin') {
                return response()->json([
                    'message' => 'Unauthorized. Only admins can reply to support tickets.'
                ], 403);
            }

            DB::beginTransaction();

            // Find the support ticket
            $ticket = clientSupportTicket::with(['user', 'messages'])
                ->where('uuid', $ticketUuid)
                ->first();

            if (!$ticket) {
                return response()->json([
                    'message' => 'Support ticket not found.'
                ], 404);
            }

            // Check if ticket is open for replies
            if ($ticket->status === 'Closed') {
                return response()->json([
                    'message' => 'Cannot reply to a closed ticket. Please reopen the ticket first.'
                ], 422);
            }

            // Validate that the client still exists
            if (!$ticket->user) {
                return response()->json([
                    'message' => 'Cannot reply to ticket. The client account no longer exists.'
                ], 422);
            }

            // Check if this would be the first admin reply to this ticket
            $existingAdminReplies = clientSupportMessages::where('ticket_uuid', $ticket->uuid)
                ->where('sender_type', 'admin')
                ->count();

            $isFirstAdminReply = $existingAdminReplies === 0;

            // Create the reply message
            $replyMessage = clientSupportMessages::create([
                'ticket_uuid' => $ticket->uuid,
                'sender_uuid' => $admin->uuid,
                'sender_type' => 'admin',
                'message' => $data['message'],
            ]);

            // Update ticket status if requested to close
            if (isset($data['close_ticket']) && $data['close_ticket'] === true) {
                $ticket->update(['status' => 'Closed']);
            }

            // Send email notification to client only on first admin reply
            if ($isFirstAdminReply) {
                try {
                    $emailData = [
                        'client_name' => $ticket->user->name,
                        'ticket_reference' => $ticket->reference,
                        'ticket_subject' => $ticket->subject,
                        'ticket_status' => $ticket->status,
                        'admin_reply' => $data['message'],
                        'admin_name' => $admin->name,
                        'response_date' => now()->format('F j, Y \a\t g:i A'),
                    ];

                    Mail::to($ticket->user->email)->send(new AdminReplyNotification($emailData));

                } catch (\Exception $mailException) {
                    // Log email failure but don't fail the entire operation
                    Log::error('Failed to send admin reply email notification', [
                        'ticket_uuid' => $ticket->uuid,
                        'client_email' => $ticket->user->email,
                        'error' => $mailException->getMessage()
                    ]);
                }
            }

            // Notify client about support reply
            $this->notificationService->notifyClientSupportReply(
                $ticket->user_uuid,
                $ticket->reference
            );

            DB::commit();

            // Reload the ticket with fresh data for response
            $ticket->load([
                'user:uuid,name,email,phone',
                'messages' => function($query) {
                    $query->with('sender:uuid,name,email,role')
                          ->orderBy('created_at', 'desc')
                          ->limit(5); // Get last 5 messages for context
                }
            ]);

            $responseMessage = 'Reply sent successfully.';
            if (isset($data['close_ticket']) && $data['close_ticket'] === true) {
                $responseMessage .= ' Ticket has been closed.';
            }
            if ($isFirstAdminReply) {
                $responseMessage .= ' Client has been notified via email.';
            }

            return response()->json([
                'status' => 'Success',
                'message' => $responseMessage,
                'data' => [
                    'ticket' => [
                        'uuid' => $ticket->uuid,
                        'reference' => $ticket->reference,
                        'subject' => $ticket->subject,
                        'status' => $ticket->status,
                        'client' => $ticket->user,
                        'total_messages' => $ticket->messages()->count(),
                        'last_reply_at' => $replyMessage->created_at,
                        'recent_messages' => $ticket->messages
                    ],
                    'reply' => [
                        'uuid' => $replyMessage->uuid,
                        'message' => $replyMessage->message,
                        'sender' => [
                            'uuid' => $admin->uuid,
                            'name' => $admin->name,
                            'role' => $admin->role
                        ],
                        'created_at' => $replyMessage->created_at
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Admin ticket reply failed', [
                'admin_id' => Auth::id(),
                'ticket_uuid' => $ticketUuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => app()->environment('production')
                    ? 'Something went wrong while sending the reply.'
                    : $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get a specific support ticket by ID with all details and messages.
     *
     * Retrieves a single support ticket with comprehensive information including
     * client details, all messages, conversation statistics, and validation status.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id The UUID of the ticket to retrieve
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSupportTicketById(Request $request, $id)
    {
        try {
            // Validate UUID format
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
                return response()->json([
                    'message' => 'Invalid ticket ID format.'
                ], 400);
            }

            // Get authenticated admin user
            $admin = Auth::user();
            if (!$admin || $admin->role !== 'admin') {
                return response()->json([
                    'message' => 'Unauthorized. Only admins can view support ticket details.'
                ], 403);
            }

            // Find the support ticket with all related data
            $ticket = clientSupportTicket::with([
                'user:uuid,name,email,phone,image,country,created_at,email_verified_at', // Client details
                'messages' => function($query) {
                    $query->with('sender:uuid,name,email,role')
                          ->orderBy('created_at', 'asc'); // Order messages chronologically
                }
            ])->where('uuid', $id)->first();

            if (!$ticket) {
                return response()->json([
                    'message' => 'Support ticket not found.'
                ], 404);
            }

            // Validate client existence and status
            $isValidClient = $ticket->user && $ticket->user->exists;
            $clientStatus = $isValidClient ? 'active' : 'invalid';

            // Check if client email is verified
            $clientEmailVerified = $ticket->user && $ticket->user->email_verified_at ? true : false;

            // Calculate message statistics
            $allMessages = $ticket->messages;
            $adminMessages = $allMessages->where('sender.role', 'admin');
            $clientMessages = $allMessages->where('sender.role', 'client');

            $lastMessage = $allMessages->last();
            $firstMessage = $allMessages->first();

            // Determine who needs to respond next
            $awaitingResponse = 'admin'; // Default
            if ($lastMessage) {
                $awaitingResponse = $lastMessage->sender->role === 'client' ? 'admin' : 'client';
            }

            // Calculate response times (basic implementation)
            $averageResponseTime = null;
            $adminResponseTimes = [];

            for ($i = 0; $i < count($allMessages) - 1; $i++) {
                $currentMessage = $allMessages[$i];
                $nextMessage = $allMessages[$i + 1];

                // If current is from client and next is from admin, calculate response time
                if ($currentMessage->sender->role === 'client' && $nextMessage->sender->role === 'admin') {
                    $responseTime = $currentMessage->created_at->diffInMinutes($nextMessage->created_at);
                    $adminResponseTimes[] = $responseTime;
                }
            }

            if (!empty($adminResponseTimes)) {
                $averageResponseTime = round(array_sum($adminResponseTimes) / count($adminResponseTimes), 2);
            }

            // Build comprehensive response
            $ticketData = [
                'uuid' => $ticket->uuid,
                'reference' => $ticket->reference,
                'subject' => $ticket->subject,
                'message' => $ticket->message, // Original ticket message
                'status' => $ticket->status,
                'created_at' => $ticket->created_at,
                'updated_at' => $ticket->updated_at,

                // Client information
                'client' => $ticket->user ? [
                    'uuid' => $ticket->user->uuid,
                    'name' => $ticket->user->name,
                    'email' => $ticket->user->email,
                    'phone' => $ticket->user->phone,
                    'image' => $ticket->user->image,
                    'country' => $ticket->user->country,
                    'email_verified' => $clientEmailVerified,
                    'account_created_at' => $ticket->user->created_at,
                ] : null,

                // Validation status
                'is_valid_client' => $isValidClient,
                'client_status' => $clientStatus,

                // Conversation statistics
                'conversation_stats' => [
                    'total_messages' => $allMessages->count(),
                    'admin_replies' => $adminMessages->count(),
                    'client_replies' => $clientMessages->count(),
                    'awaiting_response' => $awaitingResponse,
                    'last_message_at' => $lastMessage?->created_at,
                    'last_sender' => $lastMessage?->sender ? [
                        'uuid' => $lastMessage->sender->uuid,
                        'name' => $lastMessage->sender->name,
                        'role' => $lastMessage->sender->role
                    ] : null,
                    'first_message_at' => $firstMessage?->created_at,
                    'average_admin_response_time_minutes' => $averageResponseTime,
                    'conversation_duration_hours' => $firstMessage && $lastMessage ?
                        $firstMessage->created_at->diffInHours($lastMessage->created_at) : 0,
                ],

                // All messages with sender details
                'messages' => $allMessages->map(function ($message) {
                    return [
                        'uuid' => $message->uuid,
                        'message' => $message->message,
                        'sender_type' => $message->sender_type,
                        'created_at' => $message->created_at,
                        'sender' => $message->sender ? [
                            'uuid' => $message->sender->uuid,
                            'name' => $message->sender->name,
                            'email' => $message->sender->email,
                            'role' => $message->sender->role
                        ] : null,
                        // Add helpful flags for frontend
                        'is_from_admin' => $message->sender?->role === 'admin',
                        'is_from_client' => $message->sender?->role === 'client',
                    ];
                })->values(), // Reset array keys

                // Actions available
                'available_actions' => [
                    'can_reply' => $ticket->status === 'Open',
                    'can_close' => $ticket->status === 'Open',
                    'can_reopen' => $ticket->status === 'Closed',
                ]
            ];

            return response()->json([
                'status' => 'Success',
                'message' => 'Support ticket details retrieved successfully.',
                'data' => $ticketData
            ], 200);

        } catch (\Exception $e) {
            Log::error('Support ticket details retrieval failed', [
                'admin_id' => Auth::id(),
                'ticket_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => app()->environment('production')
                    ? 'Something went wrong while retrieving ticket details.'
                    : $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update the status of a support ticket.
     *
     * Allows admin to update a support ticket status between "Open" and "Closed".
     * Validates ticket existence, admin permissions, and status transitions.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id The UUID of the ticket to update
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTicketStatus(Request $request, $id)
    {
        try {
            // Validate UUID format
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
                return response()->json([
                    'message' => 'Invalid ticket ID format.'
                ], 400);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:Open,Closed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            // Get authenticated admin user
            $admin = Auth::user();
            if (!$admin || $admin->role !== 'admin') {
                return response()->json([
                    'message' => 'Unauthorized. Only admins can update ticket status.'
                ], 403);
            }

            DB::beginTransaction();

            // Find the support ticket
            $ticket = clientSupportTicket::with(['user', 'messages'])
                ->where('uuid', $id)
                ->first();

            if (!$ticket) {
                return response()->json([
                    'message' => 'Support ticket not found.'
                ], 404);
            }

            // Check if status change is needed
            if ($ticket->status === $data['status']) {
                return response()->json([
                    'message' => "Ticket is already {$data['status']}.",
                    'data' => [
                        'ticket' => [
                            'uuid' => $ticket->uuid,
                            'reference' => $ticket->reference,
                            'subject' => $ticket->subject,
                            'status' => $ticket->status,
                            'updated_at' => $ticket->updated_at
                        ]
                    ]
                ], 200);
            }

            // Validate that the client still exists
            if (!$ticket->user) {
                return response()->json([
                    'message' => 'Cannot update ticket status. Account does not exists.'
                ], 422);
            }

            $previousStatus = $ticket->status;

            // Update ticket status
            $ticket->update([
                'status' => $data['status'],
                'updated_at' => now()
            ]);

            DB::commit();

            $statusMessage = $data['status'] === 'Open' ? 'reopened' : 'closed';

            return response()->json([
                'status' => 'Success',
                'message' => "Ticket has been successfully {$statusMessage}.",
                'data' => [
                    'uuid' => $ticket->uuid,
                    'reference' => $ticket->reference,
                    'status' => $ticket->status,
                    'previous_status' => $previousStatus,
                    'updated_at' => $ticket->updated_at
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Ticket status update failed', [
                'admin_id' => Auth::id(),
                'ticket_id' => $id,
                'requested_status' => $request->input('status'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => app()->environment('production')
                    ? 'Something went wrong while updating the ticket status.'
                    : $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
