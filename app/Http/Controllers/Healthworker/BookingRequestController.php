<?php

namespace App\Http\Controllers\Healthworker;

use App\Http\Controllers\Controller;
use App\Mail\BookingConfirmationMail;
use App\Mail\BookingStatusUpdateMail;
use App\Models\BookingAppt;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BookingRequestController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    //fetch the processing appointment booking request assigned to the auth healthworker
    //by admin

    public function fetchProcessingBookingRequests(Request $request){
        try {
            // Get authenticated health worker
            $healthWorker = Auth::user();

            // Verify the user is a health worker
            if (!$healthWorker || $healthWorker->role !== 'healthworker') {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Unauthorized. Only health workers can access this endpoint.'
                ], 403);
            }

            // Validate request parameters
            $request->validate([
                'per_page' => 'nullable|integer|min:1|max:100',
                'sort' => 'nullable|in:newest,oldest,start_date_asc,start_date_desc',
                'search' => 'nullable|string|max:255'
            ]);

            // Get query parameters
            $perPage = $request->get('per_page', 10);
            $sort = $request->get('sort', 'newest');
            $search = $request->get('search');

            // Build query for pending appointments assigned to this health worker
            $query = BookingAppt::with([
                'user:uuid,name,email,phone,image,country,region,religion,address',
                'others:uuid,booking_appts_uuid,medical_services,other_extra_service',
                'recurrence:uuid,booking_appts_uuid,is_recurring,recurrence_type,recurrence_days,recurrence_end_type,recurrence_end_date,recurrence_occurrences'
            ])
            ->where('health_worker_uuid', $healthWorker->uuid)
            ->where('status', 'Processing');

            // Apply search if provided
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('booking_reference', 'LIKE', '%' . $search . '%')
                      ->orWhere('care_type', 'LIKE', '%' . $search . '%')
                      ->orWhereHas('user', function($userQuery) use ($search) {
                          $userQuery->where('name', 'LIKE', '%' . $search . '%')
                                   ->orWhere('email', 'LIKE', '%' . $search . '%');
                      });
                });
            }

            // Apply sorting
            switch ($sort) {
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'start_date_asc':
                    $query->orderBy('start_date', 'asc');
                    break;
                case 'start_date_desc':
                    $query->orderBy('start_date', 'desc');
                    break;
                case 'newest':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            // Get paginated results
            $appointments = $query->paginate($perPage);

            // Process appointments data
            $processedAppts = $appointments->getCollection()->map(function ($appointment) {
                // Calculate appointment duration
                $startDate = Carbon::parse($appointment->start_date);
                $endDate = Carbon::parse($appointment->end_date);
                $duration = $startDate->diffInDays($endDate) + 1;

                // Get medical services and other extra services
                $medicalServices = [];
                $otherExtraServices = [];

                // Get the first others record (since others is a HasMany relationship)
                $othersData = $appointment->others->first();
                if ($othersData) {
                    $medicalServices = $othersData->medical_services ?? [];
                    $otherExtraServices = $othersData->other_extra_service ?? [];
                }

                // Check if appointment is urgent (starts within 24 hours)
                $isUrgent = $startDate->isPast() || $startDate->diffInHours(now()) <= 24;

                return [
                    'id' => $appointment->uuid,
                    'booking_reference' => $appointment->booking_reference,
                    'requesting_for' => $appointment->requesting_for,
                    'someone_name' => $appointment->someone_name,
                    'someone_phone' => $appointment->someone_phone,
                    'someone_email' => $appointment->someone_email,
                    'accommodation' => $appointment->accommodation,
                    'meal' => $appointment->meal,
                    'num_of_meals' => $appointment->num_of_meals,
                    'special_notes' => $appointment->special_notes,
                    'start_date' => $appointment->start_date,
                    'end_date' => $appointment->end_date,
                    'start_time' => $appointment->start_time,
                    'end_time' => $appointment->end_time,
                    'start_time_period' => $appointment->start_time_period,
                    'end_time_period' => $appointment->end_time_period,
                    'care_type' => $appointment->care_type,
                    'care_duration' => $appointment->care_duration,
                    'care_duration_value' => $appointment->care_duration_value,
                    'status' => $appointment->status,
                    'created_at' => $appointment->created_at,
                    'duration_days' => $duration,
                    'medical_services' => $medicalServices,
                    'other_extra_services' => $otherExtraServices,
                    'is_urgent' => $isUrgent,
                    'starts_in_hours' => $startDate->diffInHours(now()),
                    'recurrence' => $appointment->recurrence ? [
                        'is_recurring' => $appointment->recurrence->is_recurring,
                        'recurrence_type' => $appointment->recurrence->recurrence_type,
                        'recurrence_days' => $appointment->recurrence->recurrence_days,
                        'recurrence_end_type' => $appointment->recurrence->recurrence_end_type,
                        'recurrence_end_date' => $appointment->recurrence->recurrence_end_date,
                        'recurrence_occurrences' => $appointment->recurrence->recurrence_occurrences,
                    ] : null,
                    'client' => [
                        'uuid' => $appointment->user->uuid,
                        'name' => $appointment->user->name,
                        'email' => $appointment->user->email,
                        'phone' => $appointment->user->phone,
                        'image' => $appointment->user->image,
                        'religion' => $appointment->user->religion,
                        'location' => [
                            'country' => $appointment->user->country,
                            'region' => $appointment->user->region,
                            'address' => $appointment->user->address
                        ]
                    ]
                ];
            });

            // Count urgent appointments
            $urgentCount = $processedAppts->where('is_urgent', true)->count();

            return response()->json([
                'status' => 'Success',
                'message' => 'Pending booking requests retrieved successfully.',
                'data' => $processedAppts,
                'meta' => [
                    'total' => $appointments->total(),
                    'per_page' => $appointments->perPage(),
                    'current_page' => $appointments->currentPage(),
                    'last_page' => $appointments->lastPage(),
                    'from' => $appointments->firstItem(),
                    'to' => $appointments->lastItem(),
                    'has_more_pages' => $appointments->hasMorePages()
                ],
                'summary' => [
                    'total_pending' => $appointments->total(),
                    'urgent_requests' => $urgentCount,
                    'applied_filters' => [
                        'search' => $search,
                        'sort' => $sort
                    ]
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error fetching pending booking requests', [
                'healthworker_uuid' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => app()->environment('production') ? 'Something went wrong.' : $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);

        }
    }



    //Confirm or accept the booking request assigned to health worker by admin
    public function acceptBookingRequest($uuid){

         // Validate the UUID format
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) {
            return response()->json([
                'message' => 'Invalid booking ID format.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Get authenticated health worker
            $healthWorker = Auth::user();

            // Verify the user is a health worker
            if (!$healthWorker || $healthWorker->role !== 'healthworker') {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Unauthorized. Only health workers can access this endpoint.'
                ], 403);
            }

            // Find the appointment
            $appointment = BookingAppt::with(['user', 'others', 'recurrence'])
                ->where('uuid', $uuid)
                ->where('health_worker_uuid', $healthWorker->uuid)
                ->first();

            if (!$appointment) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Appointment not found or not assigned to you.'
                ], 404);
            }

            // Check if appointment is in pending status
            if ($appointment->status !== 'Processing') {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Only Processing appointments can be confirmed. Current status: ' . $appointment->status
                ], 400);
            }

            // Update appointment status to Confirmed
            $appointment->update([
                'status' => 'Confirmed',
                'confirmed_at' => now(),
                'confirmed_by' => $healthWorker->uuid
            ]);

            // Load health worker relationship for email
            $appointment->load('healthWorker');

            // Send email to health worker
            try {
                Mail::to($healthWorker->email)->send(
                    new BookingConfirmationMail($appointment, $healthWorker, 'healthworker')
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send confirmation email to health worker', [
                    'healthworker_email' => $healthWorker->email,
                    'appointment_uuid' => $appointment->uuid,
                    'error' => $e->getMessage()
                ]);
            }

            // Send email to admin
            try {
                $adminEmail = config('mail.admin_email') ?? env('ADMIN_EMAIL');
                if ($adminEmail) {
                    // Create admin object for email template
                    $adminRecipient = (object) [
                        'name' => 'Admin',
                        'email' => $adminEmail
                    ];

                    Mail::to($adminEmail)->send(
                        new BookingConfirmationMail($appointment, $adminRecipient, 'admin')
                    );
                }
            } catch (\Exception $e) {
                Log::warning('Failed to send confirmation email to admin', [
                    'admin_email' => $adminEmail ?? 'not found',
                    'appointment_uuid' => $appointment->uuid,
                    'error' => $e->getMessage()
                ]);
            }

            // Notify admins about booking confirmation
            try {
                $this->notificationService->notifyAdminBookingConfirmed(
                    $appointment->booking_reference,
                    $healthWorker->name,
                    $appointment->user->name
                );
            } catch (\Exception $e) {
                Log::error('Failed to send admin notification for booking confirmation', [
                    'appointment_uuid' => $appointment->uuid,
                    'booking_reference' => $appointment->booking_reference,
                    'health_worker_uuid' => $healthWorker->uuid,
                    'error' => $e->getMessage(),
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'Success',
                'message' => 'Booking request confirmed successfully.',
                'data' => [
                    'appointment' => [
                        'uuid' => $appointment->uuid,
                        'booking_reference' => $appointment->booking_reference,
                        'status' => $appointment->status,
                        'confirmed_at' => $appointment->confirmed_at,
                    ],
                    'notifications_sent' => [
                        'health_worker_email' => true,
                        'admin_email' => true
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to confirm booking request', [
                'appointment_uuid' => $uuid,
                'health_worker_uuid' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'Error',
                'message' => app()->environment('production')
                    ? 'Failed to confirm booking request. Please try again.'
                    : $e->getMessage()
            ], 500);
        }
    }


    /**
     * Show booking appointments assigned to the health worker with pagination, filtering, and sorting.
     *
     * Retrieves booking appointments assigned to the authenticated health worker
     * with statuses: Confirmed, Ongoing, and Done. Includes related data like
     * client information and other services. Supports filtering by status,
     * searching by client name and booking reference, sorting options, and pagination.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHealthworkerAppointments(Request $request)
    {
        $healthWorker = Auth::user();

        // Verify the user is a health worker
        if (!$healthWorker || $healthWorker->role !== 'healthworker') {
            return response()->json([
                'status' => 'Error',
                'message' => 'Unauthorized. Only health workers can access this endpoint.'
            ], 403);
        }

        // Validate request parameters
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|in:Confirmed,Ongoing,Done',
            'sort' => 'nullable|string|in:newest,oldest,start_date_asc,start_date_desc,status_asc,status_desc',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get query parameters
            $search = $request->get('search');

            // Build the query for appointments assigned to this health worker
            $query = BookingAppt::with([
                'user:uuid,name,email,phone,image,country,religion,region,address,date_of_birth',
                'others:uuid,booking_appts_uuid,medical_services,other_extra_service',
                'recurrence:uuid,booking_appts_uuid,is_recurring,recurrence_type,recurrence_days,recurrence_end_type,recurrence_end_date,recurrence_occurrences'

            ])
            ->where('health_worker_uuid', $healthWorker->uuid)
            ->whereIn('status', ['Confirmed', 'Ongoing', 'Done']);

            // Apply search if provided
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('booking_reference', 'LIKE', '%' . $search . '%')
                      ->orWhereHas('user', function($userQuery) use ($search) {
                          $userQuery->where('name', 'LIKE', '%' . $search . '%');
                      });
                });
            }

            // Apply status filter if provided
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
                    'message' => 'No appointments found for this health worker.',
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
                ],
                'applied_filters' => [
                    'search' => $search,
                    'status' => $request->status,
                    'sort' => $request->sort
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Health worker appointments retrieval failed', [
                'health_worker_uuid' => $healthWorker->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => app()->environment('production') ? 'Something went wrong while retrieving appointments.' : $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update a confirmed booking request status to Ongoing.
     * Health worker can update appointment to Ongoing when they arrive at client's location.
     * Sends notification emails to both admin and health worker.
     *
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateToOngoingBookingRequest($uuid)
    {
        // Validate the UUID format
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Invalid appointment ID format.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Get authenticated health worker
            $healthWorker = Auth::user();

            // Verify the user is a health worker
            if (!$healthWorker || $healthWorker->role !== 'healthworker') {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Unauthorized. Only health workers can access this endpoint.'
                ], 403);
            }

            // Find the appointment
            $appointment = BookingAppt::with(['user', 'healthWorker', 'recurrence'])
                ->where('uuid', $uuid)
                ->where('health_worker_uuid', $healthWorker->uuid)
                ->first();

            if (!$appointment) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Appointment not found or not assigned to you.'
                ], 404);
            }

            // Check if appointment is in confirmed status
            if ($appointment->status !== 'Confirmed') {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Only Confirmed appointments can be updated to Ongoing. Current status: ' . $appointment->status
                ], 400);
            }

            // Update appointment status to Ongoing
            $appointment->update([
                'status' => 'Ongoing',
                'started_at' => now(),
                'updated_by' => $healthWorker->uuid
            ]);

            // Send email to health worker
            try {
                Mail::to($healthWorker->email)->send(
                    new BookingStatusUpdateMail($appointment, $healthWorker, 'healthworker', 'Ongoing')
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send status update email to health worker', [
                    'healthworker_email' => $healthWorker->email,
                    'appointment_uuid' => $appointment->uuid,
                    'error' => $e->getMessage()
                ]);
            }

            // Send email to admin
            try {
                $adminEmail = config('mail.admin_email') ?? env('ADMIN_EMAIL');
                if ($adminEmail) {
                    // Create admin object for email template
                    $adminRecipient = (object) [
                        'name' => 'Admin',
                        'email' => $adminEmail
                    ];

                    Mail::to($adminEmail)->send(
                        new BookingStatusUpdateMail($appointment, $adminRecipient, 'admin', 'Ongoing')
                    );
                }
            } catch (\Exception $e) {
                Log::warning('Failed to send status update email to admin', [
                    'admin_email' => $adminEmail ?? 'not found',
                    'appointment_uuid' => $appointment->uuid,
                    'error' => $e->getMessage()
                ]);
            }

            // Notify admins about booking started
            try {
                $this->notificationService->notifyAdminBookingStarted(
                    $appointment->booking_reference,
                    $healthWorker->name,
                    $appointment->user->name
                );
            } catch (\Exception $e) {
                Log::error('Failed to send admin notification for booking started', [
                    'appointment_uuid' => $appointment->uuid,
                    'booking_reference' => $appointment->booking_reference,
                    'health_worker_uuid' => $healthWorker->uuid,
                    'error' => $e->getMessage(),
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'Success',
                'message' => 'Appointment status updated to Ongoing successfully.',
                'data' => [
                    'appointment' => [
                        'uuid' => $appointment->uuid,
                        'booking_reference' => $appointment->booking_reference,
                        'status' => $appointment->status,
                        'started_at' => $appointment->started_at,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update appointment to ongoing', [
                'appointment_uuid' => $uuid,
                'health_worker_uuid' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'Error',
                'message' => app()->environment('production')
                    ? 'Failed to update appointment status. Please try again.'
                    : $e->getMessage()
            ], 500);
        }
    }
}
