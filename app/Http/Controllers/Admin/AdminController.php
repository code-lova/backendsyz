<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminResource;
use App\Models\HealthworkerReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function getAdminDetails(Request $request){

        $user = $request->user();

        return new AdminResource($user);
    }

    public function updateAdminProfile(Request $request){
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            // Validate the request data
            $validator = Validator::make($request->all(), [
                'name' => ['sometimes', 'string', 'max:255'],
                'email' => [
                    'sometimes',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($user->id),
                ],
                'phone' => [
                    'sometimes',
                    'string',
                    'regex:/^\+?[1-9]\d{9,14}$/',
                    Rule::unique('users', 'phone')->ignore($user->id),
                ],
                'country' => ['sometimes', 'string', 'max:255'],
                'region' => ['sometimes', 'string', 'max:255'],
                'address' => ['sometimes', 'string', 'max:500'],
                'religion' => ['nullable', 'string', 'max:255'],
                'gender' => ['sometimes', 'string', Rule::in(['Male', 'Female', 'Non-binary', 'Transgender', 'Bigender'])],
                'about' => ['sometimes', 'string', 'max:1000'],
            ], [
                'email.unique' => 'This email is already in use.',
                'phone.unique' => 'This phone number is already registered.',
                'phone.regex' => 'Please enter a valid phone number.',
                'about.max' => 'About section must not exceed 1000 characters.',
                'gender.in' => 'Please select a valid gender option.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get only the validated data
            $validatedData = $validator->validated();

            // Update the user with validated data
            $user->update($validatedData);

            // Return success response with updated user data
            return response()->json([
                'status' => 'Success',
                'message' => 'Profile updated successfully.',
                'data' => [
                    'user' => new AdminResource($user->fresh()) // Get fresh data from database
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Admin profile update failed', [
                'admin_id' => Auth::id(),
                'admin_uuid' => Auth::user()->uuid ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => app()->environment('production')
                    ? 'Something went wrong. Please try again later.'
                    : $e->getMessage(),
                'error' => !app()->environment('production') ? $e->getMessage() : null
            ], 500);
        }
    }



    /**
     * Fetch all health worker ratings and reviews for admin.
     * 
     * Retrieves comprehensive rating and review data with search, filtering,
     * pagination, and summary statistics for administrative oversight.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchHealthWorkerRatingReview(Request $request)
    {
        try {
            // Validate request parameters
            $validator = Validator::make($request->all(), [
                'rating' => 'nullable|integer|min:1|max:5',
                'medical_service' => 'nullable|string|max:255',
                'sort' => 'nullable|string|in:newest,oldest,rating_high,rating_low,healthworker_name,client_name',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'limit' => 'nullable|integer|min:1|max:100',
                'search' => 'nullable|string|max:255',
                'healthworker_uuid' => 'nullable|uuid|exists:users,uuid',
                'client_uuid' => 'nullable|uuid|exists:users,uuid',
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
                    'message' => 'Unauthorized. Only admins can access rating and review data.'
                ], 403);
            }

            // Build the query for health worker reviews - simplified eager loading
            $query = HealthworkerReview::with([
                'client:uuid,name,email,phone,image,country,created_at',
                'healthworker:uuid,name,email,phone,image,country,region',
                'bookingAppt:uuid,booking_reference,start_date,end_date,start_time,end_time,care_type,care_duration,care_duration_value,status',
                'bookingAppt.others:uuid,booking_appts_uuid,medical_services,other_extra_service'
            ]);

            // Apply search filter
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('review', 'like', "%$search%")
                      ->orWhereHas('healthworker', function($hwQuery) use ($search) {
                          $hwQuery->where('name', 'like', "%$search%")
                                  ->orWhere('email', 'like', "%$search%");
                      })
                      ->orWhereHas('client', function($clientQuery) use ($search) {
                          $clientQuery->where('name', 'like', "%$search%")
                                     ->orWhere('email', 'like', "%$search%");
                      })
                      ->orWhereHas('bookingAppt', function($bookingQuery) use ($search) {
                          $bookingQuery->where('booking_reference', 'like', "%$search%");
                      });
                });
            }

            // Apply rating filter
            if ($request->has('rating') && $request->rating) {
                $query->where('rating', $request->rating);
            }

            // Apply health worker filter
            if ($request->has('healthworker_uuid') && $request->healthworker_uuid) {
                $query->where('healthworker_uuid', $request->healthworker_uuid);
            }

            // Apply client filter
            if ($request->has('client_uuid') && $request->client_uuid) {
                $query->where('client_uuid', $request->client_uuid);
            }

            // Apply medical service filter - simplified approach
            if ($request->has('medical_service') && $request->medical_service) {
                $medicalService = $request->medical_service;
                
                Log::info('Medical service filter applied', [
                    'medical_service' => $medicalService,
                    'admin_id' => Auth::id()
                ]);
                
                $query->whereHas('bookingAppt.others', function($othersQuery) use ($medicalService) {
                    $othersQuery->where('medical_services', 'like', '%"' . $medicalService . '"%');
                });
            }

            // Apply sorting
            switch ($request->sort) {
                case 'newest':
                    $query->orderBy('reviewed_at', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('reviewed_at', 'asc');
                    break;
                case 'rating_high':
                    $query->orderBy('rating', 'desc')->orderBy('reviewed_at', 'desc');
                    break;
                case 'rating_low':
                    $query->orderBy('rating', 'asc')->orderBy('reviewed_at', 'desc');
                    break;
                case 'healthworker_name':
                    $query->join('users as hw', 'healthworker_reviews.healthworker_uuid', '=', 'hw.uuid')
                          ->orderBy('hw.name', 'asc')
                          ->select('healthworker_reviews.*');
                    break;
                case 'client_name':
                    $query->join('users as cl', 'healthworker_reviews.client_uuid', '=', 'cl.uuid')
                          ->orderBy('cl.name', 'asc')
                          ->select('healthworker_reviews.*');
                    break;
                default:
                    $query->orderBy('reviewed_at', 'desc');
                    break;
            }

            // Get pagination parameters
            $perPage = $request->per_page ?? $request->limit ?? 15;
            $page = $request->page ?? 1;

            // Execute the paginated query
            $reviews = $query->paginate($perPage, ['*'], 'page', $page);

            // Process reviews to add computed fields
            $processedReviews = $reviews->getCollection()->map(function ($review) {
                // Calculate review age
                try {
                    $reviewAge = $review->reviewed_at instanceof \Carbon\Carbon 
                        ? $review->reviewed_at->diffForHumans()
                        : \Carbon\Carbon::parse($review->reviewed_at)->diffForHumans();
                } catch (\Exception $e) {
                    $reviewAge = 'Unknown';
                }
                
                // Get medical services
                $medicalServices = collect();
                if ($review->bookingAppt && $review->bookingAppt->others) {
                    $medicalServices = $review->bookingAppt->others
                        ->pluck('medical_services')
                        ->flatten()
                        ->filter()
                        ->unique()
                        ->values();
                }

                // Add computed fields
                $review->review_age = $reviewAge;
                $review->medical_services_provided = $medicalServices;
                
                // Calculate appointment duration
                if ($review->bookingAppt && $review->bookingAppt->start_date && $review->bookingAppt->end_date) {
                    try {
                        $review->appointment_duration_days = \Carbon\Carbon::parse($review->bookingAppt->start_date)
                            ->diffInDays(\Carbon\Carbon::parse($review->bookingAppt->end_date)) + 1;
                    } catch (\Exception $e) {
                        $review->appointment_duration_days = 1;
                    }
                } else {
                    $review->appointment_duration_days = $review->bookingAppt ? 1 : null;
                }

                // Add rating description
                $ratingDescriptions = [
                    1 => 'Very Poor',
                    2 => 'Poor', 
                    3 => 'Average',
                    4 => 'Good',
                    5 => 'Excellent'
                ];
                $review->rating_description = $ratingDescriptions[$review->rating] ?? 'Unknown';

                // Add validation flags
                $review->has_written_review = !empty($review->review);
                $review->is_high_rating = $review->rating >= 4;
                $review->is_low_rating = $review->rating <= 2;

                return $review;
            });

            // Update the collection
            $reviews->setCollection($processedReviews);

            // Handle empty results
            if ($reviews->isEmpty()) {
                return response()->json([
                    'message' => 'No health worker reviews found.',
                    'data' => [],
                    'meta' => [
                        'total' => 0,
                        'per_page' => $perPage,
                        'current_page' => $page,
                        'last_page' => 1,
                        'from' => null,
                        'to' => null
                    ],
                    'summary' => [
                        'total_reviews' => 0,
                        'average_rating' => 0,
                        'rating_distribution' => [],
                        'top_rated_healthworkers' => [],
                        'recent_reviews_count' => 0
                    ]
                ], 200);
            }

            // Calculate summary statistics
            $allReviews = HealthworkerReview::all();
            $totalReviews = $allReviews->count();
            $averageRating = $totalReviews > 0 ? round($allReviews->avg('rating'), 2) : 0;

            // Rating distribution
            $ratingDistribution = [];
            for ($i = 1; $i <= 5; $i++) {
                $count = $allReviews->where('rating', $i)->count();
                $percentage = $totalReviews > 0 ? round(($count / $totalReviews) * 100, 1) : 0;
                $ratingDistribution[] = [
                    'rating' => $i,
                    'count' => $count,
                    'percentage' => $percentage
                ];
            }

            // Top rated health workers
            $topRatedHealthWorkers = HealthworkerReview::select('healthworker_uuid')
                ->selectRaw('COUNT(*) as review_count')
                ->selectRaw('AVG(rating) as avg_rating')
                ->with('healthworker:uuid,name,email,image')
                ->groupBy('healthworker_uuid')
                ->havingRaw('COUNT(*) >= 3')
                ->orderByRaw('AVG(rating) DESC, COUNT(*) DESC')
                ->limit(5)
                ->get()
                ->map(function($item) {
                    return [
                        'healthworker' => $item->healthworker,
                        'review_count' => (int)$item->review_count,
                        'average_rating' => round($item->avg_rating, 2)
                    ];
                });

            // Recent reviews count
            $recentReviewsCount = HealthworkerReview::where('reviewed_at', '>=', now()->subDays(30))->count();

            return response()->json([
                'status' => 'Success',
                'message' => 'Health worker ratings and reviews retrieved successfully.',
                'data' => $reviews->items(),
                'meta' => [
                    'total' => $reviews->total(),
                    'per_page' => $reviews->perPage(),
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'from' => $reviews->firstItem(),
                    'to' => $reviews->lastItem(),
                    'has_more_pages' => $reviews->hasMorePages()
                ],
                'summary' => [
                    'total_reviews' => $totalReviews,
                    'average_rating' => $averageRating,
                    'rating_distribution' => $ratingDistribution,
                    'top_rated_healthworkers' => $topRatedHealthWorkers,
                    'recent_reviews_count' => $recentReviewsCount
                ],
                'filters_applied' => [
                    'search' => $request->search ?? null,
                    'rating' => $request->rating ?? null,
                    'medical_service' => $request->medical_service ?? null,
                    'healthworker_uuid' => $request->healthworker_uuid ?? null,
                    'client_uuid' => $request->client_uuid ?? null,
                    'sort' => $request->sort ?? 'newest'
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Health worker reviews retrieval failed', [
                'admin_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => app()->environment('production') 
                    ? 'Something went wrong while retrieving reviews data.' 
                    : $e->getMessage(),
                'error' => !app()->environment('production') ? $e->getMessage() : null
            ], 500);
        }
    }

}
