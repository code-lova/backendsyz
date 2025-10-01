<?php

namespace App\Http\Controllers\Healthworker;

use App\Http\Controllers\Controller;
use App\Models\BookingAppt;
use App\Models\HealthworkerReview;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Get total count of appointments assigned to the authenticated health worker.
     * Returns counts for appointment statuses.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAppointmentCounts()
    {
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

            // Get current date for filtering
            $today = Carbon::today();
            $currentMonth = Carbon::now()->startOfMonth();

            // Get appointment counts by status
            $appointmentCounts = BookingAppt::where('health_worker_uuid', $healthWorker->uuid)
                ->selectRaw('
                    COUNT(*) as total_appointments,
                    SUM(CASE WHEN status = "Processing" THEN 1 ELSE 0 END) as pending_appointments,
                    SUM(CASE WHEN status = "Confirmed" THEN 1 ELSE 0 END) as confirmed_appointments,
                    SUM(CASE WHEN status = "Ongoing" THEN 1 ELSE 0 END) as ongoing_appointments,
                    SUM(CASE WHEN status = "Done" THEN 1 ELSE 0 END) as completed_appointments
                ')
                ->first();

            // Get upcoming appointments (next 7 days)
            $upcomingCount = BookingAppt::where('health_worker_uuid', $healthWorker->uuid)
                ->whereIn('status', ['Confirmed', 'Processing'])
                ->whereBetween('start_date', [$today, $today->copy()->addDays(7)])
                ->count();

            // Get today's appointments
            $todayCount = BookingAppt::where('health_worker_uuid', $healthWorker->uuid)
                ->whereIn('status', ['Confirmed', 'Ongoing'])
                ->whereDate('start_date', $today)
                ->count();

            // Get this month's appointments
            $thisMonthCount = BookingAppt::where('health_worker_uuid', $healthWorker->uuid)
                ->whereDate('start_date', '>=', $currentMonth)
                ->count();

            return response()->json([
                'status' => 'Success',
                'message' => 'Appointment counts retrieved successfully.',
                'data' => [
                    'total_appointments' => (int) $appointmentCounts->total_appointments,
                    'pending_appointments' => (int) $appointmentCounts->pending_appointments,
                    'confirmed_appointments' => (int) $appointmentCounts->confirmed_appointments,
                    'ongoing_appointments' => (int) $appointmentCounts->ongoing_appointments,
                    'completed_appointments' => (int) $appointmentCounts->completed_appointments,
                    'cancelled_appointments' => (int) $appointmentCounts->cancelled_appointments,
                    'upcoming_7_days' => $upcomingCount,
                    'today_appointments' => $todayCount,
                    'this_month_total' => $thisMonthCount,
                    'generated_at' => now()->toISOString()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve appointment counts', [
                'health_worker_uuid' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'Error',
                'message' => app()->environment('production')
                    ? 'Failed to retrieve appointment statistics. Please try again.'
                    : $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get health worker ratings statistics for dashboard.
     * Returns current month rating, overall rating, and weekly trends for current month.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRatingsStat()
    {
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

            // Get current month and year boundaries
            $currentMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            // Get overall ratings statistics
            $overallStats = HealthworkerReview::where('healthworker_uuid', $healthWorker->uuid)
                ->selectRaw('
                    COUNT(*) as total_reviews,
                    AVG(rating) as average_rating,
                    MAX(rating) as highest_rating,
                    MIN(rating) as lowest_rating
                ')
                ->first();

            // Get current month ratings statistics
            $monthlyStats = HealthworkerReview::where('healthworker_uuid', $healthWorker->uuid)
                ->whereBetween('reviewed_at', [$currentMonth, $endOfMonth])
                ->selectRaw('
                    COUNT(*) as monthly_reviews,
                    AVG(rating) as monthly_average_rating
                ')
                ->first();

            // Get weekly trends for current month (4-5 weeks)
            $weeklyTrends = [];
            $startOfMonth = $currentMonth->copy();
            
            for ($week = 1; $week <= 5; $week++) {
                $weekStart = $startOfMonth->copy()->addWeeks($week - 1);
                $weekEnd = $weekStart->copy()->endOfWeek();
                
                // Stop if week start is beyond current month
                if ($weekStart->month !== $currentMonth->month) {
                    break;
                }
                
                // Adjust week end to not exceed current month
                if ($weekEnd->month !== $currentMonth->month) {
                    $weekEnd = $endOfMonth->copy();
                }

                $weeklyRating = HealthworkerReview::where('healthworker_uuid', $healthWorker->uuid)
                    ->whereBetween('reviewed_at', [$weekStart, $weekEnd])
                    ->selectRaw('
                        COUNT(*) as reviews_count,
                        AVG(rating) as average_rating
                    ')
                    ->first();

                $weeklyTrends[] = [
                    'week' => $week,
                    'week_period' => $weekStart->format('M j') . ' - ' . $weekEnd->format('M j'),
                    'reviews_count' => (int) $weeklyRating->reviews_count,
                    'average_rating' => $weeklyRating->reviews_count > 0 
                        ? round((float) $weeklyRating->average_rating, 1) 
                        : 0.0
                ];
            }

            // Calculate rating distribution
            $ratingDistribution = HealthworkerReview::where('healthworker_uuid', $healthWorker->uuid)
                ->selectRaw('
                    rating,
                    COUNT(*) as count
                ')
                ->groupBy('rating')
                ->orderBy('rating', 'desc')
                ->get()
                ->pluck('count', 'rating')
                ->toArray();

            return response()->json([
                'status' => 'Success',
                'message' => 'Rating statistics retrieved successfully.',
                'data' => [
                    'overall' => [
                        'total_reviews' => (int) $overallStats->total_reviews,
                        'average_rating' => $overallStats->total_reviews > 0 
                            ? round((float) $overallStats->average_rating, 1) 
                            : 0.0,
                        'highest_rating' => (int) ($overallStats->highest_rating ?? 0),
                        'lowest_rating' => (int) ($overallStats->lowest_rating ?? 0)
                    ],
                    'current_month' => [
                        'month' => $currentMonth->format('F Y'),
                        'total_reviews' => (int) $monthlyStats->monthly_reviews,
                        'average_rating' => $monthlyStats->monthly_reviews > 0 
                            ? round((float) $monthlyStats->monthly_average_rating, 1) 
                            : 0.0
                    ],
                    'weekly_trends' => $weeklyTrends,
                    'rating_distribution' => $ratingDistribution,
                    'generated_at' => now()->toISOString()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve ratings statistics', [
                'health_worker_uuid' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'Error',
                'message' => app()->environment('production')
                    ? 'Failed to retrieve rating statistics. Please try again.'
                    : $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get appointment statistics per month for the current year.
     * Returns monthly appointment counts for line chart visualization.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAppointmentStatsPerMonth(Request $request)
    {
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
                'year' => 'nullable|integer|min:2020|max:' . (date('Y') + 1),
                'status' => 'nullable|string|in:all,confirmed,ongoing,completed'
            ]);

            // Get year parameter or default to current year
            $year = $request->get('year', date('Y'));
            $statusFilter = $request->get('status', 'all');

            // Create date boundaries for the year
            $startOfYear = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endOfYear = Carbon::createFromDate($year, 12, 31)->endOfYear();

            // Build base query
            $query = BookingAppt::where('health_worker_uuid', $healthWorker->uuid)
                ->whereBetween('start_date', [$startOfYear, $endOfYear]);

            // Apply status filter if specified
            if ($statusFilter !== 'all') {
                switch ($statusFilter) {
                    case 'confirmed':
                        $query->where('status', 'Confirmed');
                        break;
                    case 'ongoing':
                        $query->where('status', 'Ongoing');
                        break;
                    case 'completed':
                        $query->where('status', 'Done');
                        break;
                }
            }

            // Get monthly appointment counts
            $monthlyStats = $query
                ->selectRaw('
                    MONTH(start_date) as month,
                    MONTHNAME(start_date) as month_name,
                    COUNT(*) as appointment_count,
                    SUM(CASE WHEN status = "Done" THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN status = "Confirmed" THEN 1 ELSE 0 END) as confirmed_count,
                    SUM(CASE WHEN status = "Ongoing" THEN 1 ELSE 0 END) as ongoing_count
                ')
                ->groupBy('month', 'month_name')
                ->orderBy('month')
                ->get();

            // Create array for all 12 months with zero counts for missing months
            $monthlyData = [];
            for ($month = 1; $month <= 12; $month++) {
                $monthName = Carbon::createFromDate($year, $month, 1)->format('F');
                $existingData = $monthlyStats->firstWhere('month', $month);

                $monthlyData[] = [
                    'month' => $month,
                    'month_name' => $monthName,
                    'appointment_count' => $existingData ? (int) $existingData->appointment_count : 0,
                    'completed_count' => $existingData ? (int) $existingData->completed_count : 0,
                    'confirmed_count' => $existingData ? (int) $existingData->confirmed_count : 0,
                    'ongoing_count' => $existingData ? (int) $existingData->ongoing_count : 0
                ];
            }

            // Calculate summary statistics
            $totalAppointments = array_sum(array_column($monthlyData, 'appointment_count'));
            $totalCompleted = array_sum(array_column($monthlyData, 'completed_count'));
            $totalConfirmed = array_sum(array_column($monthlyData, 'confirmed_count'));
            $totalOngoing = array_sum(array_column($monthlyData, 'ongoing_count'));

            // Find peak month
            $peakMonth = collect($monthlyData)->sortByDesc('appointment_count')->first();

            return response()->json([
                'status' => 'Success',
                'message' => 'Monthly appointment statistics retrieved successfully.',
                'data' => [
                    'year' => $year,
                    'status_filter' => $statusFilter,
                    'monthly_data' => $monthlyData,
                    'summary' => [
                        'total_appointments' => $totalAppointments,
                        'total_completed' => $totalCompleted,
                        'total_confirmed' => $totalConfirmed,
                        'total_ongoing' => $totalOngoing,
                        'completion_rate' => $totalAppointments > 0 
                            ? round(($totalCompleted / $totalAppointments) * 100, 1) 
                            : 0.0,
                    ],
                    'peak_month' => [
                        'month' => $peakMonth['month_name'],
                        'count' => $peakMonth['appointment_count']
                    ],
                    'generated_at' => now()->toISOString()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve monthly appointment statistics', [
                'health_worker_uuid' => Auth::id(),
                'year' => $request->get('year'),
                'status_filter' => $request->get('status'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'Error',
                'message' => app()->environment('production')
                    ? 'Failed to retrieve monthly appointment statistics. Please try again.'
                    : $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get the 3 most recent appointments for the authenticated health worker.
     * Returns essential appointment details for dashboard display.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecentAppointments()
    {
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

            // Get the 3 most recent appointments with minimal client information
            $recentAppointments = BookingAppt::with([
                'user:uuid,image,name'
            ])
            ->where('health_worker_uuid', $healthWorker->uuid)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

            // Format the appointments data for dashboard display
            $formattedAppointments = $recentAppointments->map(function ($appointment) {
                return [
                    'start_date' => $appointment->start_date,
                    'user' => [
                        'uuid' => $appointment->user->uuid,
                        'image' => $appointment->user->image,
                        'name' => $appointment->user->name
                    ]
                ];
            });

            return response()->json([
                'status' => 'Success',
                'message' => 'Recent appointments retrieved successfully.',
                'data' => [
                    'recent_appointments' => $formattedAppointments,
                    'showing_count' => $formattedAppointments->count(),
                    'generated_at' => now()->toISOString()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve recent appointments', [
                'health_worker_uuid' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'Error',
                'message' => app()->environment('production')
                    ? 'Failed to retrieve recent appointments. Please try again.'
                    : $e->getMessage()
            ], 500);
        }
    }


    
}
