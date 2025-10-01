<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingAppt;
use App\Models\HealthworkerReview;
use App\Models\SupportMessage;
use App\Models\User;
use App\Models\clientSupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Get comprehensive admin dashboard statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // Get authenticated admin user
            $admin = Auth::user();
            if (!$admin || $admin->role !== 'admin') {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Unauthorized. Only admins can access dashboard statistics.'
                ], 403);
            }

            // Get current month start date
            $currentMonthStart = now()->startOfMonth();

            // 1. APPOINTMENT STATISTICS
            $totalAppointments = BookingAppt::count();
            $appointmentsThisMonth = BookingAppt::where('created_at', '>=', $currentMonthStart)->count();

            // 2. USER STATISTICS
            $totalUsers = User::count();
            $totalClients = User::where('role', 'client')->count();
            $totalHealthWorkers = User::where('role', 'healthworker')->count();

            // CLIENT STATISTICS (nested under users)
            $clientStats = [
                'total' => $totalClients,
                'active' => User::where('role', 'client')->where('is_active', '1')->count(),
                'inactive' => User::where('role', 'client')->where('is_active', '0')->count(),
                'verified' => User::where('role', 'client')->whereNotNull('email_verified_at')->count(),
                'unverified' => User::where('role', 'client')->whereNull('email_verified_at')->count(),
                'registered_this_month' => User::where('role', 'client')
                    ->where('created_at', '>=', $currentMonthStart)
                    ->count(),
                'two_factor_enabled' => User::where('role', 'client')
                    ->where('two_factor_enabled', true)
                    ->count(),
            ];

            // HEALTH WORKER STATISTICS (nested under users)
            $healthWorkerStats = [
                'total' => $totalHealthWorkers,
                'active' => User::where('role', 'healthworker')->where('is_active', '1')->count(),
                'inactive' => User::where('role', 'healthworker')->where('is_active', '0')->count(),
                'verified' => User::where('role', 'healthworker')->whereNotNull('email_verified_at')->count(),
                'unverified' => User::where('role', 'healthworker')->whereNull('email_verified_at')->count(),
                'registered_this_month' => User::where('role', 'healthworker')
                    ->where('created_at', '>=', $currentMonthStart)
                    ->count(),
                'two_factor_enabled' => User::where('role', 'healthworker')
                    ->where('two_factor_enabled', true)
                    ->count(),
                // Health worker specific stats
                'with_ratings' => HealthworkerReview::distinct('healthworker_uuid')->count(),
                'average_rating' => HealthworkerReview::join('users', 'healthworker_reviews.healthworker_uuid', '=', 'users.uuid')
                    ->where('users.role', 'healthworker')
                    ->whereNotNull('healthworker_reviews.rating')
                    ->avg('healthworker_reviews.rating'),
                'with_appointments' => BookingAppt::distinct('health_worker_uuid')
                    ->whereNotNull('health_worker_uuid')
                    ->count(),
            ];

            // Round the average rating for health workers
            $healthWorkerStats['average_rating'] = $healthWorkerStats['average_rating'] 
                ? round($healthWorkerStats['average_rating'], 2) 
                : 0;

            // 3. HEALTH WORKER RATING STATISTICS
            $totalRatings = HealthworkerReview::whereNotNull('rating')->count();
            $ratingsThisMonth = HealthworkerReview::whereNotNull('rating')
                ->where('reviewed_at', '>=', $currentMonthStart)
                ->count();

            // 4. HEALTH WORKER REVIEW STATISTICS (written reviews)
            $totalReviews = HealthworkerReview::whereNotNull('review')
                ->where('review', '!=', '')
                ->count();
            $reviewsThisMonth = HealthworkerReview::whereNotNull('review')
                ->where('review', '!=', '')
                ->where('reviewed_at', '>=', $currentMonthStart)
                ->count();

            // 5. CLIENT SUPPORT TICKET STATISTICS
            $totalClientTickets = clientSupportTicket::count();
            $clientTicketsThisMonth = clientSupportTicket::where('created_at', '>=', $currentMonthStart)->count();

            // 6. HEALTH WORKER SUPPORT MESSAGE STATISTICS
            $totalHealthWorkerMessages = SupportMessage::count();
            $healthWorkerMessagesThisMonth = SupportMessage::where('created_at', '>=', $currentMonthStart)->count();

            // ADDITIONAL STATISTICS FOR BETTER INSIGHTS
            // Active vs Inactive users
            $activeUsers = User::where('is_active', '1')->count();
            $inactiveUsers = User::where('is_active', '0')->count();

            // Verified vs Unverified users
            $verifiedUsers = User::whereNotNull('email_verified_at')->count();
            $unverifiedUsers = User::whereNull('email_verified_at')->count();

            // Average rating
            $averageRating = HealthworkerReview::whereNotNull('rating')->avg('rating');
            $averageRating = $averageRating ? round($averageRating, 2) : 0;

            // Appointment status distribution
            $appointmentStats = BookingAppt::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // Support message status distribution
            $supportMessageStats = SupportMessage::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // Monthly growth calculations
            $previousMonthStart = now()->startOfMonth()->subMonth();
            $previousMonthEnd = now()->startOfMonth()->subSecond();

            $appointmentsPreviousMonth = BookingAppt::whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])->count();
            $ratingsPreviousMonth = HealthworkerReview::whereNotNull('rating')
                ->whereBetween('reviewed_at', [$previousMonthStart, $previousMonthEnd])
                ->count();
            $reviewsPreviousMonth = HealthworkerReview::whereNotNull('review')
                ->where('review', '!=', '')
                ->whereBetween('reviewed_at', [$previousMonthStart, $previousMonthEnd])
                ->count();

            return response()->json([
                'status' => 'Success',
                'message' => 'Dashboard statistics retrieved successfully.',
                'data' => [
                    'appointments' => [
                        'total' => $totalAppointments,
                        'current_month' => $appointmentsThisMonth,
                        'previous_month' => $appointmentsPreviousMonth,
                        'growth_percentage' => $appointmentsPreviousMonth > 0 
                            ? round((($appointmentsThisMonth - $appointmentsPreviousMonth) / $appointmentsPreviousMonth) * 100, 2)
                            : ($appointmentsThisMonth > 0 ? 100 : 0),
                        'status_breakdown' => $appointmentStats
                    ],
                    'users' => [
                        'total' => $totalUsers,
                        'clients' => $clientStats,
                        'health_workers' => $healthWorkerStats,
                        'overall' => [
                            'active' => $activeUsers,
                            'inactive' => $inactiveUsers,
                            'verified' => $verifiedUsers,
                            'unverified' => $unverifiedUsers,
                            'active_percentage' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0,
                            'verified_percentage' => $totalUsers > 0 ? round(($verifiedUsers / $totalUsers) * 100, 2) : 0,
                        ]
                    ],
                    'health_worker_ratings' => [
                        'total' => $totalRatings,
                        'current_month' => $ratingsThisMonth,
                        'previous_month' => $ratingsPreviousMonth,
                        'growth_percentage' => $ratingsPreviousMonth > 0 
                            ? round((($ratingsThisMonth - $ratingsPreviousMonth) / $ratingsPreviousMonth) * 100, 2)
                            : ($ratingsThisMonth > 0 ? 100 : 0),
                        'average_rating' => $averageRating
                    ],
                    'health_worker_reviews' => [
                        'total' => $totalReviews,
                        'current_month' => $reviewsThisMonth,
                        'previous_month' => $reviewsPreviousMonth,
                        'growth_percentage' => $reviewsPreviousMonth > 0 
                            ? round((($reviewsThisMonth - $reviewsPreviousMonth) / $reviewsPreviousMonth) * 100, 2)
                            : ($reviewsThisMonth > 0 ? 100 : 0)
                    ],
                    'client_support_tickets' => [
                        'total' => $totalClientTickets,
                        'current_month' => $clientTicketsThisMonth
                    ],
                    'health_worker_support_messages' => [
                        'total' => $totalHealthWorkerMessages,
                        'current_month' => $healthWorkerMessagesThisMonth,
                        'status_breakdown' => $supportMessageStats
                    ],
                    'summary' => [
                        'total_platform_interactions' => $totalAppointments + $totalRatings + $totalReviews + $totalClientTickets + $totalHealthWorkerMessages,
                        'user_engagement_rate' => $totalUsers > 0 ? round((($totalRatings + $totalReviews) / $totalUsers) * 100, 2) : 0,
                        'active_user_percentage' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0,
                        'verified_user_percentage' => $totalUsers > 0 ? round(($verifiedUsers / $totalUsers) * 100, 2) : 0
                    ],
                    'metadata' => [
                        'generated_at' => now()->toISOString(),
                        'current_month' => now()->format('F Y'),
                        'previous_month' => now()->subMonth()->format('F Y'),
                        'admin_user' => $admin->name
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Dashboard statistics retrieval failed', [
                'admin_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'Error',
                'message' => app()->environment('production')
                    ? 'Something went wrong while retrieving dashboard statistics.'
                    : $e->getMessage(),
                'error' => !app()->environment('production') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get appointment count per month for the current year (for line chart visualization)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAppointmentCountPerMonth()
    {
        try {
            $admin = Auth::user();
            if (!$admin || $admin->role !== 'admin') {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Unauthorized. Only admins can access appointment statistics.'
                ], 403);
            }

            $currentYear = now()->year;
            
            // Initialize months array with zero counts
            $monthlyData = [
                'January' => 0,
                'February' => 0,
                'March' => 0,
                'April' => 0,
                'May' => 0,
                'June' => 0,
                'July' => 0,
                'August' => 0,
                'September' => 0,
                'October' => 0,
                'November' => 0,
                'December' => 0
            ];

            // Get appointment counts grouped by month for the current year
            $appointmentCounts = BookingAppt::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->whereYear('created_at', $currentYear)
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('count', 'month')
                ->toArray();

            // Map numeric months to month names and populate data
            $monthNames = [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
            ];

            foreach ($appointmentCounts as $monthNum => $count) {
                if (isset($monthNames[$monthNum])) {
                    $monthlyData[$monthNames[$monthNum]] = $count;
                }
            }

            // Prepare chart-ready data
            $chartData = [
                'labels' => array_keys($monthlyData),
                'data' => array_values($monthlyData),
                'total_appointments' => array_sum($monthlyData),
                'highest_month' => array_search(max($monthlyData), $monthlyData),
                'lowest_month' => array_search(min($monthlyData), $monthlyData),
                'average_per_month' => round(array_sum($monthlyData) / 12, 2)
            ];

            return response()->json([
                'status' => 'Success',
                'message' => 'Monthly appointment statistics retrieved successfully.',
                'data' => [
                    'year' => $currentYear,
                    'monthly_breakdown' => $monthlyData,
                    'chart_data' => $chartData,
                    'insights' => [
                        'total_appointments' => $chartData['total_appointments'],
                        'average_per_month' => $chartData['average_per_month'],
                        'peak_month' => $chartData['highest_month'],
                        'lowest_month' => $chartData['lowest_month'],
                        'peak_count' => max($monthlyData),
                        'lowest_count' => min($monthlyData)
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Monthly appointment statistics retrieval failed', [
                'admin_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'Error',
                'message' => 'Failed to retrieve monthly appointment statistics.',
                'error' => !app()->environment('production') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get appointment status count statistics filterable by month for the current year
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAppointmentStatusCountStats(Request $request)
    {
        try {
            $admin = Auth::user();
            if (!$admin || $admin->role !== 'admin') {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Unauthorized. Only admins can access appointment statistics.'
                ], 403);
            }

            $currentYear = now()->year;
            $selectedMonth = $request->query('month'); // Optional month filter (1-12)

            // Define appointment statuses
            $appointmentStatuses = ['Pending', 'Processing', 'Confirmed', 'Ongoing', 'Cancel', 'Done'];

            // Base query for current year
            $query = BookingAppt::whereYear('created_at', $currentYear);

            // Apply month filter if provided
            if ($selectedMonth && is_numeric($selectedMonth) && $selectedMonth >= 1 && $selectedMonth <= 12) {
                $query->whereMonth('created_at', $selectedMonth);
            }

            // Get total appointments
            $totalAppointments = $query->count();

            // Get status counts
            $statusCounts = $query->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // Initialize status breakdown with zero counts
            $statusBreakdown = [];
            foreach ($appointmentStatuses as $status) {
                $statusBreakdown[$status] = $statusCounts[$status] ?? 0;
            }

            // Month names for reference
            $monthNames = [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
            ];

            return response()->json([
                'status' => 'Success',
                'message' => 'Appointment status statistics retrieved successfully.',
                'data' => [
                    'year' => $currentYear,
                    'month' => $selectedMonth ? $monthNames[$selectedMonth] : 'All Months',
                    'total_appointments' => $totalAppointments,
                    'status_counts' => $statusBreakdown,
                    'chart_data' => [
                        'labels' => array_keys($statusBreakdown),
                        'data' => array_values($statusBreakdown)
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Appointment status statistics retrieval failed', [
                'admin_id' => Auth::id(),
                'selected_month' => $request->query('month'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'Error',
                'message' => 'Failed to retrieve appointment status statistics.',
                'error' => !app()->environment('production') ? $e->getMessage() : null
            ], 500);
        }
    }
}
