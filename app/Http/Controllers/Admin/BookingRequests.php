<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BookingStatusNotification;
use App\Models\BookingAppt;
use App\Models\HealthworkerReview;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class BookingRequests extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
     /**
     * Allows admin to view all bookings
     * and the details of a specific booking request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listBookingRequests(Request $request){
        // Validate request parameters
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|in:Pending,Processing,Confirmed,Ongoing,Done,Cancelled',
            'sort' => 'nullable|string|in:newest,oldest,start_date_asc,start_date_desc,status_asc,status_desc',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string', // booking_reference search
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Build the query for all bookings (admin)
            $query = BookingAppt::with([
                'user:uuid,name,email,phone,image,country',
                'healthWorker:uuid,name,email,phone,image,country,region',
                'healthWorker.guidedRateSystem',
                'healthWorker.guidedRateSystem.serviceTypes',
                'others:uuid,booking_appts_uuid,medical_services,other_extra_service',
                'recurrence:uuid,booking_appts_uuid,is_recurring,recurrence_type,recurrence_days,recurrence_end_type,recurrence_end_date,recurrence_occurrences'
            ]);

            // Search by booking_reference
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where('booking_reference', 'like', "%$search%");
            }

            // Apply status filter
            if ($request->status && $request->status !== 'All') {
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
                case 'start_date_asc':
                    $query->orderBy('start_date', 'asc')->orderBy('start_time', 'asc');
                    break;
                case 'start_date_desc':
                    $query->orderBy('start_date', 'desc')->orderBy('start_time', 'desc');
                    break;
                case 'status_asc':
                    $query->orderBy('status', 'asc');
                    break;
                case 'status_desc':
                    $query->orderBy('status', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc'); // Default sorting
                    break;
            }

            // Get pagination parameters
            $perPage = $request->per_page ?? 10;
            $page = $request->page ?? 1;

            // Execute the paginated query
            $bookings = $query->paginate($perPage, ['*'], 'page', $page);

            if ($bookings->isEmpty()) {
                return response()->json([
                    'message' => 'No bookings found.',
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

            return response()->json([
                'status' => 'Success',
                'data' => $bookings->items(),
                'meta' => [
                    'total' => $bookings->total(),
                    'per_page' => $bookings->perPage(),
                    'current_page' => $bookings->currentPage(),
                    'last_page' => $bookings->lastPage(),
                    'from' => $bookings->firstItem(),
                    'to' => $bookings->lastItem(),
                    'has_more_pages' => $bookings->hasMorePages()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Booking retrieval failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => app()->environment('production') ? 'Something went wrong while retrieving bookings.' : $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process a specific booking appointment by UUID.
     *
     * Allows admin to mark their pending appointments as Processing by updating the status
     * from 'Pending' to 'Processing' and optionally assign a health worker to the booking.
     * Pending, Processing, and can be Confirmed by the health worker..
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id The UUID of the booking appointment to process
     * @return \Illuminate\Http\JsonResponse
     */
    public function processingBookingRequest(Request $request, $id)
    {
        try {
            $request = request();
            $id = $request->route('id');

             // Validate UUID format
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
                return response()->json([
                    'message' => 'Invalid booking ID format.'
                ], 400);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'action' => 'required|string|in:processing',
                'health_worker_uuid' => 'nullable|string|exists:users,uuid',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            // Find the booking to be processed
            $booking = BookingAppt::with(['user', 'recurrence'])->where('uuid', $id)->first();

            if (!$booking) {
                return response()->json([
                    'message' => 'Booking Request not found.'
                ], 404);
            }

            // Only allow if status is Pending, Processing, or Confirmed
            if (!in_array($booking->status, ['Pending', 'Processing', 'Confirmed'])) {
                return response()->json([
                    'message' => 'Only pending, processing, or confirmed booking requests can be processed.'
                ], 422);
            }

            if ($data['action'] === 'processing') {
                // Check if this is a health worker reassignment
                $isReassignment = false;
                $previousHealthWorker = null;

                if (isset($data['health_worker_uuid']) && $data['health_worker_uuid']) {
                    // Check if booking already has a health worker and it's different
                    if ($booking->health_worker_uuid && $booking->health_worker_uuid !== $data['health_worker_uuid']) {
                        $isReassignment = true;
                        $previousHealthWorker = User::where('uuid', $booking->health_worker_uuid)->first();
                    }

                    // Check if the health worker is already assigned to another appointment with Processing status
                    $existingProcessingAppointment = BookingAppt::where('health_worker_uuid', $data['health_worker_uuid'])
                        ->where('status', 'Processing')
                        ->where('uuid', '!=', $booking->uuid) // Exclude current booking
                        ->first();

                    if ($existingProcessingAppointment) {
                        return response()->json([
                            'message' => 'This health worker is already assigned to another appointment that is awaiting confirmation.',
                            'data' => [
                                'existing_appointment_reference' => $existingProcessingAppointment->booking_reference,
                                'health_worker_status' => 'Processing - Awaiting Confirmation'
                            ]
                        ], 422);
                    }
                }

                // Prepare update data
                $updateData = ['status' => 'Processing'];

                // Add health worker UUID if provided
                if (isset($data['health_worker_uuid']) && $data['health_worker_uuid']) {
                    $updateData['health_worker_uuid'] = $data['health_worker_uuid'];
                }

                // Update booking status to Processing and optionally assign health worker
                $booking->update($updateData);

                // Send mail to client, health worker, and admin on status update
                try {
                    $clientEmail = $booking->user->email ?? null;
                    $adminEmail = config('mail.admin_email') ?? env('ADMIN_EMAIL');
                    $healthWorkerEmail = null;
                    $healthWorker = null;

                    // Get health worker details if assigned
                    if (isset($data['health_worker_uuid']) && $data['health_worker_uuid']) {
                        $healthWorker = User::where('uuid', $data['health_worker_uuid'])->first();
                        if ($healthWorker) {
                            $healthWorkerEmail = $healthWorker->email;
                        }
                    }

                    // Base mail data
                    $baseMail = [
                        'booking_reference' => $booking->booking_reference,
                        'status' => 'Processing',
                        'client_name' => $booking->user->name ?? '',
                        'processed_at' => now()->toDateTimeString(),
                        'start_date' => $booking->start_date,
                        'end_date' => $booking->end_date,
                        'start_time' => $booking->start_time,
                        'end_time' => $booking->end_time,
                        'start_time_period' => $booking->start_time_period,
                        'end_time_period' => $booking->end_time_period,
                        // Recurrence data
                        'is_recurring' => $booking->recurrence?->is_recurring ?? 'No',
                        'recurrence_type' => $booking->recurrence?->recurrence_type,
                        'recurrence_days' => $booking->recurrence?->recurrence_days,
                        'recurrence_end_type' => $booking->recurrence?->recurrence_end_type,
                        'recurrence_end_date' => $booking->recurrence?->recurrence_end_date,
                        'recurrence_occurrences' => $booking->recurrence?->recurrence_occurrences,
                    ];

                    // Send email to client
                    if ($clientEmail) {
                        $clientMailData = $baseMail;

                        if ($healthWorker) {
                            if ($isReassignment) {
                                $clientMailData['health_worker_reassigned'] = true;
                                $clientMailData['new_health_worker_name'] = $healthWorker->name;
                                if ($previousHealthWorker) {
                                    $clientMailData['previous_health_worker_name'] = $previousHealthWorker->name;
                                }
                            } else {
                                $clientMailData['health_worker_assigned'] = true;
                                $clientMailData['health_worker_name'] = $healthWorker->name;
                            }
                        }

                        Mail::to($clientEmail)->send(new BookingStatusNotification($clientMailData));
                    }

                    // Send email to admin
                    if ($adminEmail) {
                        $adminMailData = $baseMail;
                        $adminMailData['is_for_admin'] = true;
                        if ($healthWorker) {
                            $adminMailData['healthworker_name'] = $healthWorker->name;
                        }

                        Mail::to($adminEmail)->send(new BookingStatusNotification($adminMailData));
                    }

                    // Send email to health worker if assigned
                    if ($healthWorkerEmail && $healthWorker) {
                        $healthWorkerMailData = $baseMail;
                        $healthWorkerMailData['is_for_healthworker'] = true;
                        $healthWorkerMailData['healthworker_name'] = $healthWorker->name;

                        Mail::to($healthWorkerEmail)->send(new BookingStatusNotification($healthWorkerMailData));
                    }

                    // Send notifications
                    if ($healthWorker) {
                        // Notify client about booking assignment
                        $this->notificationService->notifyClientBookingAssigned(
                            $booking->user_uuid,
                            $booking->booking_reference,
                            $healthWorker->name
                        );

                        // Notify health worker about new booking assignment
                        $this->notificationService->notifyHealthWorkerNewBooking(
                            $healthWorker->uuid,
                            $booking->booking_reference,
                            $booking->user->name
                        );
                    }

                } catch (\Exception $e) {
                    Log::error('Failed to send booking processing notification', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                Log::info('Booking request processed successfully', [
                    'booking_uuid' => $booking->uuid,
                    'booking_reference' => $booking->booking_reference,
                    'health_worker_assigned' => isset($data['health_worker_uuid']) ? $data['health_worker_uuid'] : 'No assignment',
                    'is_reassignment' => $isReassignment,
                    'previous_health_worker' => $previousHealthWorker ? $previousHealthWorker->uuid : null,
                    'processed_at' => now()
                ]);

                $responseMessage = 'Booking Request is now being processed.';
                if (isset($data['health_worker_uuid']) && $data['health_worker_uuid']) {
                    if ($isReassignment) {
                        $responseMessage .= ' Health worker has been reassigned.';
                    } else {
                        $responseMessage .= ' Health worker has been assigned.';
                    }
                }

                return response()->json([
                    'status' => 'Success',
                    'message' => $responseMessage
                ], 200);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Marking appointment as done or review failed', [
                'booking_uuid' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => app()->environment('production') ? 'Something went wrong while completing the appointment.' : $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Done a specific booking appointment by UUID.
     *
     * Allows admin to mark their ongoing appointments as done by updating the status
     * from 'Ongoing' to 'Done' and recording the review and rating details.
     * Only ongoing appointments can be marked as done.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id The UUID of the booking appointment to cancel
     * @return \Illuminate\Http\JsonResponse
     */
    public function doneBookingRequest(Request $request, $id)
    {

        $request = request();
        $id = $request->route('id');

        // Validate UUID format
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            return response()->json([
                'message' => 'Invalid booking ID format.'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Done',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
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

            // Find the booking to be updated to done by admin
            $booking = BookingAppt::where('uuid', $id)->first();

            if (!$booking) {
                return response()->json([
                    'message' => 'Booking Request not found.'
                ], 404);
            }

            // Only allow if status is Ongoing
            if ($booking->status !== 'Ongoing') {
                return response()->json([
                    'message' => 'Only ongoing booking request can be marked as done.'
                ], 422);
            }

            // Get healthworker uuid
            $healthworkerUuid = $booking->health_worker_uuid;
            if (!$healthworkerUuid) {
                return response()->json([
                    'message' => 'No healthworker assigned to this appointment.'
                ], 422);
            }
            $clientUuid = $booking->user_uuid;
            if (!$clientUuid) {
                return response()->json([
                    'message' => 'No client found for this Booking Request.'
                ], 422);
            }

            // Update booking status to Done
            $booking->update([
                'status' => 'Done',
            ]);

            // Store review and rating
            HealthworkerReview::create([
                'booking_appt_uuid' => $booking->uuid,
                'client_uuid' => $clientUuid,
                'healthworker_uuid' => $healthworkerUuid,
                'rating' => $data['rating'],
                'review' => $data['review'] ?? null,
                'reviewed_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'Success',
                'message' => 'Appointment Done and review submitted.',
            ], 200);

            // Send mail to client and admin and healthworker on status update
            try {
                $clientEmail = $booking->user->email ?? null;
                $healthworkerEmail = $booking->healthWorker->email ?? null;
                $adminEmail = config('mail.admin_email') ?? env('ADMIN_EMAIL');
                // Email data for client
                $mailDataForClient = [
                    'booking_reference' => $booking->booking_reference,
                    'status' => 'Done',
                    'client_name' => $booking->user->name ?? '',
                    'healthworker_name' => $booking->healthWorker->name ?? '',
                    'completed_at' => now()->toDateTimeString(),
                ];

                // Email data for admin (different messaging)
                $mailDataForAdmin = [
                    'booking_reference' => $booking->booking_reference,
                    'status' => 'Done',
                    'client_name' => $booking->user->name ?? '',
                    'healthworker_name' => $booking->healthWorker->name ?? '',
                    'completed_at' => now()->toDateTimeString(),
                    'is_for_admin' => true, // Flag to identify admin email
                ];

                // Email data for health worker (different addressing)
                $mailDataForHealthWorker = [
                    'booking_reference' => $booking->booking_reference,
                    'status' => 'Done',
                    'client_name' => $booking->user->name ?? '',
                    'healthworker_name' => $booking->healthWorker->name ?? '',
                    'completed_at' => now()->toDateTimeString(),
                    'is_for_healthworker' => true, // Flag to identify health worker email
                ];

                if ($clientEmail) {
                    Mail::to($clientEmail)->send(new BookingStatusNotification($mailDataForClient));
                }
                if ($adminEmail) {
                    Mail::to($adminEmail)->send(new BookingStatusNotification($mailDataForAdmin));
                }
                if ($healthworkerEmail) {
                    Mail::to($healthworkerEmail)->send(new BookingStatusNotification($mailDataForHealthWorker));
                }
            } catch (\Exception $e) {
                Log::error('Failed to send booking done notification', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Marking appointment as done or review failed', [
                'booking_uuid' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => app()->environment('production') ? 'Something went wrong while completing the appointment.' : $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Cancel a specific booking appointment by UUID.
     *
     * Allows admin to cancel their pending appointments by updating the status
     * from 'Pending' to 'Cancelled' and recording the cancellation details.
     * Only pending appointments can be cancelled.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id The UUID of the booking appointment to cancel
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelBookingRequest(Request $request, $id)
    {
        $user = Auth::user();

        // Validate the UUID format
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            return response()->json([
                'message' => 'Invalid booking ID format.'
            ], 400);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'action' => 'required|string|in:cancel',
            'reason_for_cancellation' => 'required_if:action,cancel|string|min:10|max:500',
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

            // Find the specific booking by UUID to be cancelled
            $booking = BookingAppt::where('uuid', $id)->first();

            if (!$booking) {
                return response()->json([
                    'message' => 'Booking Request not found.'
                ], 404);
            }

            // Check if booking can be cancelled (only pending appointments)
            if ($booking->status !== 'Pending') {
                return response()->json([
                    'message' => 'Only pending bookings can be cancelled. Current status: ' . $booking->status
                ], 422);
            }

            // Handle cancellation action
            if ($data['action'] === 'cancel') {
                $booking->update([
                    'status' => 'Cancelled',
                    'reason_for_cancellation' => $data['reason_for_cancellation'],
                    'cancelled_by_user_uuid' => $user->uuid,
                ]);

                DB::commit();

                 // Send mail to client and admin on status update
                try {
                    $clientEmail = $booking->user->email ?? null;
                    $adminEmail = config('mail.admin_email') ?? env('ADMIN_EMAIL');

                    // Email data for client
                    $mailDataForClient = [
                        'booking_reference' => $booking->booking_reference,
                        'status' => 'Cancelled',
                        'client_name' => $booking->user->name ?? '',
                        'cancelled_at' => now()->toDateTimeString(),
                    ];

                    // Email data for admin (different messaging)
                    $mailDataForAdmin = [
                        'booking_reference' => $booking->booking_reference,
                        'status' => 'Cancelled',
                        'client_name' => $booking->user->name ?? '',
                        'cancelled_at' => now()->toDateTimeString(),
                        'is_for_admin' => true, // Flag to identify admin email
                    ];
                    if ($clientEmail) {
                        Mail::to($clientEmail)->send(new BookingStatusNotification($mailDataForClient));
                    }
                    if ($adminEmail) {
                        Mail::to($adminEmail)->send(new BookingStatusNotification($mailDataForAdmin));
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send booking Cancelled notification', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                Log::info('Booking request cancelled successfully', [
                    'user_id' => $user->id,
                    'booking_uuid' => $booking->uuid,
                    'booking_reference' => $booking->booking_reference,
                    'user_uuid' => $user->uuid,
                    'reason' => $data['reason_for_cancellation'],
                    'cancelled_at' => now()
                ]);

                return response()->json([
                    'status' => 'Success',
                    'message' => 'Booking appointment cancelled successfully.',
                    'data' => [
                        'booking_reference' => $booking->booking_reference,
                        'status' => $booking->status,
                        'cancelled_at' => now()->toDateTimeString()
                    ]
                ], 200);
            }

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Booking cancellation failed', [
                'user_id' => $user->id,
                'booking_uuid' => $id,
                'action' => $data['action'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => app()->environment('production') ? 'Something went wrong while processing your request.' : $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }


      /**
     * Delete a specific booking appointment by UUID.
     *
     * Handles the deletion of a specific booking appointment identified by UUID
     * with proper error handling and database transactions to ensure data integrity.
     * Admin have the ability to delete any booking.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id The UUID of the booking appointment to delete
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyBookingRequest($id)
    {
        // Validate the UUID format
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            return response()->json([
                'message' => 'Invalid booking ID format.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Find the specific booking by UUID by admin
            $booking = BookingAppt::with('others')
                ->where('uuid', $id)
                ->first();

            if (!$booking) {
                return response()->json([
                    'message' => 'Booking Request not found.'
                ], 404);
            }

            //Get the client UUID who created the booking
            $clientUuid = $booking->user_uuid;
            if (!$clientUuid) {
                return response()->json([
                    'message' => 'No client found for this Booking Request.'
                ], 422);
            }


            // Check if booking can be deleted (e.g., not in progress, confirmed, ongoing or done)
            if (in_array($booking->status, ['Processing', 'Confirmed', 'Ongoing', 'Done'])) {
                return response()->json([
                    'message' => 'Cannot delete booking appointment.'
                ], 422);
            }

            // Delete related booking appointment others first
            $booking->others()->delete();

            // Delete the main booking appointment
            $booking->delete();

            DB::commit();

            Log::info('Booking request deleted successfully', [
                'booking_uuid' => $booking->uuid,
                'user_uuid' => $clientUuid,
                'deleted_at' => now()
            ]);

            return response()->json([
                'status' => 'Success',
                'message' => 'Booking request deleted successfully.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Booking deletion failed', [
                'booking_uuid' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => app()->environment('production') ? 'Something went wrong while deleting the booking.' : $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
