<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Mail\BookingStatusNotification;
use App\Mail\NewBookingRequest;
use App\Models\BookingAppt;
use App\Models\BookingApptOthers;
use App\Models\BookingRecurrence;
use App\Models\HealthworkerReview;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BookingAppointmentController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

/**
 * Handle the creation of a booking appointment.
 *
 * Validates the request data and processes the creation
 * of a new booking appointment in the system.
 *
 * @param \Illuminate\Http\Request $request
 * @return \Illuminate\Http\JsonResponse
 */

    public function create(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'requesting_for' => 'required|string|in:Self,Someone',

            'someone_name' => 'nullable|string|max:255',
            'someone_phone' => ['nullable', 'regex:/^\+?[0-9]{10,15}$/', 'unique:users,phone,' . $user->id],
            'someone_email' => 'nullable|email|unique:users,email,' . $user->id,

            'care_duration' => 'required|string|in:Hourly,Shift',
            'care_duration_value' => 'required|integer',

            'care_type' => 'required|string|in:Live-out,Live-in',
            'accommodation' => 'nullable|string|in:Yes,No',
            'meal' => 'nullable|in:Yes,No',
            'num_of_meals' => 'nullable|integer',

            'special_notes' => 'nullable|min:10|max:1000',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'start_time_period' => 'required|string|in:AM,PM',
            'end_time_period' => 'required|string|in:AM,PM',

            'medical_services' => 'nullable|array',
            'medical_services.*' => 'string|max:255',
            'other_extra_service' => 'nullable|array',
            'other_extra_service.*' => 'string|max:255',

            // Recurrence validation rules
            'is_recurring' => 'nullable|string|in:Yes,No',
            'recurrence_type' => 'nullable|required_if:is_recurring,Yes|string|in:Daily,Weekly,Monthly',
            'recurrence_days' => 'nullable|required_if:recurrence_type,Weekly|array',
            'recurrence_days.*' => 'string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'recurrence_end_type' => 'nullable|required_if:is_recurring,Yes|string|in:date,occurrences',
            'recurrence_end_date' => 'nullable|required_if:recurrence_end_type,date|date|after:end_date',
            'recurrence_occurrences' => 'nullable|required_if:recurrence_end_type,occurrences|integer|min:1|max:52',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if (BookingAppt::where('user_uuid', $user->uuid)
            ->whereIn('status', ['Pending', 'Ongoing', 'Confirmed'])
            ->exists()) {
            return response()->json([
                'message' => 'You have an existing appointment that is not completed.',
                'errors' => ['Your existing appointment is either pending, ongoing, or confirmed.']
            ], 409);
        }

        try {

            DB::beginTransaction();

            // Generate unique booking reference
            $bookingReference = $this->generateBookingReference();

            $bookingApt = BookingAppt::create(array_merge(
                [
                    'user_uuid' => $user->uuid,
                    'booking_reference' => $bookingReference
                ],
                Arr::only($data, [
                    'requesting_for', 'someone_name', 'someone_phone', 'someone_email',
                    'other_medical_information', 'care_duration', 'care_duration_value',
                    'care_type', 'accommodation', 'meal', 'num_of_meals',
                    'special_notes', 'start_date', 'end_date', 'start_time', 'end_time',
                    'start_time_period', 'end_time_period', 'status'
                ])
            ));

            // Insert new Booking Appt others if applicable
            //Removed redundant json_encode() since Laravel will cast arrays automatically via $casts.
            //in model
            $bookingApt->others()->create([
                'medical_services' => $data['medical_services'],
                'other_extra_service' => $data['other_extra_service'] ?? [],
            ]);

            // Insert recurrence settings if applicable
            $isRecurring = $data['is_recurring'] ?? 'No';
            $bookingApt->recurrence()->create([
                'is_recurring' => $isRecurring,
                'recurrence_type' => $isRecurring === 'Yes' ? ($data['recurrence_type'] ?? null) : null,
                'recurrence_days' => $isRecurring === 'Yes' && ($data['recurrence_type'] ?? null) === 'Weekly'
                    ? ($data['recurrence_days'] ?? [])
                    : null,
                'recurrence_end_type' => $isRecurring === 'Yes' ? ($data['recurrence_end_type'] ?? null) : null,
                'recurrence_end_date' => $isRecurring === 'Yes' && ($data['recurrence_end_type'] ?? null) === 'date'
                    ? ($data['recurrence_end_date'] ?? null)
                    : null,
                'recurrence_occurrences' => $isRecurring === 'Yes' && ($data['recurrence_end_type'] ?? null) === 'occurrences'
                    ? ($data['recurrence_occurrences'] ?? null)
                    : null,
            ]);

            // Send emails to client and admin after booking creation
            try {
                // Prepare email data
                $clientEmail = $user->email;
                $adminEmail = config('mail.admin_email') ?? env('ADMIN_EMAIL');

                // Prepare comprehensive booking data
                $baseMailData = [
                    'booking_reference' => $bookingReference,
                    'client_name' => $user->name,
                    'client_email' => $user->email,
                    'start_date' => $bookingApt->start_date,
                    'end_date' => $bookingApt->end_date,
                    'start_time' => $bookingApt->start_time,
                    'end_time' => $bookingApt->end_time,
                    'start_time_period' => $bookingApt->start_time_period,
                    'end_time_period' => $bookingApt->end_time_period,
                    'care_type' => $bookingApt->care_type,
                    'care_duration' => $bookingApt->care_duration,
                    'care_duration_value' => $bookingApt->care_duration_value,
                    'requesting_for' => $bookingApt->requesting_for,
                    'accommodation' => $bookingApt->accommodation,
                    'meal' => $bookingApt->meal,
                    'num_of_meals' => $bookingApt->num_of_meals,
                    'special_notes' => $bookingApt->special_notes,
                    // Recurrence fields
                    'is_recurring' => $isRecurring,
                    'recurrence_type' => $isRecurring === 'Yes' ? ($data['recurrence_type'] ?? null) : null,
                    'recurrence_days' => $isRecurring === 'Yes' && ($data['recurrence_type'] ?? null) === 'Weekly'
                        ? ($data['recurrence_days'] ?? [])
                        : null,
                    'recurrence_end_type' => $isRecurring === 'Yes' ? ($data['recurrence_end_type'] ?? null) : null,
                    'recurrence_end_date' => $isRecurring === 'Yes' && ($data['recurrence_end_type'] ?? null) === 'date'
                        ? ($data['recurrence_end_date'] ?? null)
                        : null,
                    'recurrence_occurrences' => $isRecurring === 'Yes' && ($data['recurrence_end_type'] ?? null) === 'occurrences'
                        ? ($data['recurrence_occurrences'] ?? null)
                        : null,
                ];

                // Client email (recipient_type not set, so it defaults to client view)
                $clientMailData = $baseMailData;
                Mail::to($clientEmail)->send(new NewBookingRequest($clientMailData));

                // Admin email with admin-specific data
                if ($adminEmail) {
                    $adminMailData = array_merge($baseMailData, [
                        'recipient_type' => 'admin'
                    ]);
                    Mail::to($adminEmail)->send(new NewBookingRequest($adminMailData));
                }

                // Notify admins about new booking request
                $this->notificationService->notifyAdminNewBookingRequest(
                    $bookingReference,
                    $user->name
                );

            } catch (\Exception $e) {
                Log::error('Failed to send booking emails', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                // Do not fail booking creation if email fails
            }

            DB::commit();

            return response()->json([
                'status' => 'Success',
                'message' => 'Booking created successfully.',
                'data' => [
                    'booking_reference' => $bookingReference,
                    'booking_id' => $bookingApt->uuid
                ]
            ], 200);

        }catch(\Exception $e){
            DB::rollBack();
            Log::error('Booking creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => app()->environment('production') ? 'Something went wrong' : $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        };

    }

    /**
     * Show booking appointments with pagination, filtering, and sorting.
     *
     * Retrieves booking appointments with their related data including
     * user information, health worker details, and other services.
     * Supports filtering by status, sorting options, and pagination.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $user = Auth::user();

        // Validate request parameters
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|in:Pending,Processing,Confirmed,Ongoing,Done,Cancelled',
            'sort' => 'nullable|string|in:newest,oldest,start_date_asc,start_date_desc,status_asc,status_desc',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Build the query
            $query = BookingAppt::with([
                'user:uuid,name,email,phone,image,country',
                'healthWorker:uuid,name,email,phone,image,country,region',
                'others:uuid,booking_appts_uuid,medical_services,other_extra_service',
                'recurrence:uuid,booking_appts_uuid,is_recurring,recurrence_type,recurrence_days,recurrence_end_type,recurrence_end_date,recurrence_occurrences'
            ])->where('user_uuid', $user->uuid);

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
                    'message' => 'No bookings found for this user.',
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

            // Hide the id field from each booking response
            $bookings->getCollection()->each(function ($booking) {
                $booking->makeHidden(['id']);
            });

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
                'user_id' => $user->id,
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
     * Cancel a specific booking appointment by UUID.
     *
     * Allows clients to cancel their pending appointments by updating the status
     * from 'Pending' to 'Cancelled' and recording the cancellation details.
     * Only pending appointments can be cancelled.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id The UUID of the booking appointment to cancel
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelAppointment(Request $request, $id)
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

            // Find the specific booking by UUID and ensure it belongs to the authenticated user
            $booking = BookingAppt::where('uuid', $id)
                ->where('user_uuid', $user->uuid)
                ->first();

            if (!$booking) {
                return response()->json([
                    'message' => 'Booking Appointment not found.'
                ], 404);
            }

            // Check if booking can be cancelled (only pending appointments)
            if ($booking->status !== 'Pending') {
                return response()->json([
                    'message' => 'Only pending appointments can be cancelled. Current status: ' . $booking->status
                ], 422);
            }

            // Handle cancellation action
            if ($data['action'] === 'cancel') {
                $booking->update([
                    'status' => 'Cancelled',
                    'reason_for_cancellation' => $data['reason_for_cancellation'],
                    'cancelled_by_user_uuid' => $user->uuid,
                ]);

                // Send mail to client and admin on status update
                try {
                    $clientEmail = $user->email ?? null;
                    $adminEmail = config('mail.admin_email') ?? env('ADMIN_EMAIL');

                    // Email data for client
                    $mailDataForClient = [
                        'booking_reference' => $booking->booking_reference,
                        'status' => 'Cancelled',
                        'client_name' => $user->name ?? '',
                        'cancelled_at' => now()->toDateTimeString(),
                    ];

                    // Email data for admin (different messaging)
                    $mailDataForAdmin = [
                        'booking_reference' => $booking->booking_reference,
                        'status' => 'Cancelled',
                        'client_name' => $user->name ?? '',
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

                DB::commit();

                Log::info('Booking appointment cancelled successfully', [
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
     * Only allows deletion of bookings that belong to the authenticated user.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id The UUID of the booking appointment to delete
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $user = Auth::user();

        // Validate the UUID format
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            return response()->json([
                'message' => 'Invalid booking ID format.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Find the specific booking by UUID and ensure it belongs to the authenticated user
            $booking = BookingAppt::with('others')
                ->where('uuid', $id)
                ->where('user_uuid', $user->uuid)
                ->first();

            if (!$booking) {
                return response()->json([
                    'message' => 'Booking Appointment not found.'
                ], 404);
            }

            // Check if booking can be deleted (e.g., not in progress or completed)
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

            Log::info('Booking appointment deleted successfully', [
                'user_id' => $user->id,
                'booking_uuid' => $booking->uuid,
                'user_uuid' => $user->uuid,
                'deleted_at' => now()
            ]);

            return response()->json([
                'status' => 'Success',
                'message' => 'Booking appointment deleted successfully.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Booking deletion failed', [
                'user_id' => $user->id,
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

    /**
     * Generate a unique booking reference number.
     *
     * Creates a 12-character alphanumeric uppercase reference number
     * that is guaranteed to be unique in the database.
     *
     * @return string
     */
    private function generateBookingReference(): string
    {
        do {
            // Generate 12-character alphanumeric uppercase string
            $reference = 'BK' . strtoupper(substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 10));

            // Check if this reference already exists
            $exists = BookingAppt::where('booking_reference', $reference)->exists();

        } while ($exists);

        return $reference;
    }

    //Mark appointment done and save review and ratings
    public function doneAppointment(Request $request, $id) {
        $user = Auth::user();

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

            // Find the booking and ensure it belongs to the user
            $booking = BookingAppt::with(['user:uuid,name,email', 'healthWorker:uuid,name,email', 'recurrence'])
                ->where('uuid', $id)
                ->where('user_uuid', $user->uuid)
                ->first();

            if (!$booking) {
                return response()->json([
                    'message' => 'Booking Appointment not found.'
                ], 404);
            }

            // Only allow if status is Ongoing
            if ($booking->status !== 'Ongoing') {
                return response()->json([
                    'message' => 'Only ongoing appointments can be marked as done.'
                ], 422);
            }

            // Get healthworker uuid
            $healthworkerUuid = $booking->health_worker_uuid;
            if (!$healthworkerUuid) {
                return response()->json([
                    'message' => 'No healthworker assigned to this appointment.'
                ], 422);
            }

            // Update booking status to Done
            $booking->update([
                'status' => 'Done',
            ]);

            // Store review and rating
            HealthworkerReview::create([
                'booking_appt_uuid' => $booking->uuid,
                'client_uuid' => $user->uuid,
                'healthworker_uuid' => $healthworkerUuid,
                'rating' => $data['rating'],
                'review' => $data['review'] ?? null,
                'reviewed_at' => now(),
            ]);

            // Send mail to client and admin and healthworker on status update
            try {
                $clientEmail = $user->email ?? null;
                $healthworkerEmail = $booking->healthWorker->email ?? null;
                $adminEmail = config('mail.admin_email') ?? env('ADMIN_EMAIL');

                // Email data for client
                $mailDataForClient = [
                    'booking_reference' => $booking->booking_reference,
                    'status' => 'Done',
                    'client_name' => $user->name ?? '',
                    'healthworker_name' => $booking->healthWorker->name ?? '',
                    'completed_at' => now()->toDateTimeString(),
                ];

                // Email data for admin (different messaging)
                $mailDataForAdmin = [
                    'booking_reference' => $booking->booking_reference,
                    'status' => 'Done',
                    'client_name' => $user->name ?? '',
                    'healthworker_name' => $booking->healthWorker->name ?? '',
                    'completed_at' => now()->toDateTimeString(),
                    'is_for_admin' => true, // Flag to identify admin email
                ];

                // Email data for health worker (different addressing)
                $mailDataForHealthWorker = [
                    'booking_reference' => $booking->booking_reference,
                    'status' => 'Done',
                    'client_name' => $user->name ?? '',
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
                    'booking_uuid' => $booking->uuid,
                    'error' => $e->getMessage(),
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'Success',
                'message' => 'Appointment Done and review submitted.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Marking appointment as done or review failed', [
                'user_id' => $user->id,
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
}
