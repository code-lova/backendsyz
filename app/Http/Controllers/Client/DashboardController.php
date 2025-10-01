<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    //get the total booking for a client in a month according to weeks
    public function monthlyBookingActivity(){
        try {
            // Get the currently authenticated user
            $user = Auth::user();

            // Get month/year from query params, default to current month/year
            // Used for filtering bookings by selected month/year
            $month = request('month') ? intval(request('month')) : now()->month;
            $year = request('year') ? intval(request('year')) : now()->year;

            // Optimize: Use date range instead of whereYear/whereMonth for better index usage
            $startOfMonth = now()->create($year, $month, 1)->startOfMonth();
            $endOfMonth = $startOfMonth->copy()->endOfMonth();

            // Fetch bookings with optimized query using date range
            $bookings = DB::table('booking_appts')
                ->where('user_uuid', $user->uuid)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->select('created_at')
                ->get();

            // Initialize week counters (Week 1-5)
            // Each key represents a week in the month
            $weeks = [
                'Week 1' => 0,
                'Week 2' => 0,
                'Week 3' => 0,
                'Week 4' => 0,
                'Week 5' => 0,
            ];

            // Loop through bookings and increment the count for the correct week
            foreach ($bookings as $booking) {
                // Get the week number of the booking (ISO week)
                $weekNum = intval(date('W', strtotime($booking->created_at)));
                // Get the week number of the first day of the month
                $firstDayOfMonth = date('Y-m-01', strtotime($booking->created_at));
                $firstWeekNum = intval(date('W', strtotime($firstDayOfMonth)));
                // Calculate week of month (1-based)
                $weekOfMonth = ($weekNum - $firstWeekNum) + 1;
                $weekLabel = 'Week ' . $weekOfMonth;
                // Increment the count for the correct week
                if (isset($weeks[$weekLabel])) {
                    $weeks[$weekLabel]++;
                }
            }

            // Prepare chart data for frontend (array of week/count objects)
            $chartData = [];
            foreach ($weeks as $week => $count) {
                $chartData[] = [
                    'week' => $week,
                    'appointments' => $count,
                ];
            }

            // Return the result as JSON for the frontend chart
            return response()->json([
                'message' => 'Monthly booking activity fetched successfully.',
                'data' => $chartData,
                'month' => $month,
                'year' => $year,
            ], 200);

        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('Getting total booking activity failed', [
                'user_id' => Auth::user()->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            // Return error response
            return response()->json([
                'message' => app()->environment('production')
                    ? 'Something went wrong. Please try again later.'
                    : $e->getMessage(),
            ], 500);
        }

    }


    public function getTotalAppointment(){
        try {
            $user = Auth::user();
            
            // Optimize: Use query with index-friendly where clause
            $totalAppointments = DB::table('booking_appts')
                ->where('user_uuid', $user->uuid)
                ->count('id');

            // Return the total count as JSON
            return response()->json([
                'message' => 'Total appointments fetched successfully.',
                'total_appointments' => $totalAppointments,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Getting total appointments failed', [
                'user_id' => Auth::user()->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => app()->environment('production')
                    ? 'Something went wrong. Please try again later.'
                    : $e->getMessage(),
            ], 500);
        }
    }


    public function getNextAppointment(){
        try {
            // Get the currently authenticated user
            $user = Auth::user();

            // Optimize: Use where clause instead of whereDate for better index usage
            $today = now()->startOfDay();

            $nextAppointment = DB::table('booking_appts')
                ->where('user_uuid', $user->uuid)
                ->where('start_date', '>=', $today)
                ->select('uuid', 'start_date', 'status', 'start_time', 'start_time_period')
                ->orderBy('start_date', 'asc')
                ->first();

            if (!$nextAppointment) {
                return response()->json([
                    'message' => 'No upcoming appointments found.',
                    'appointment' => null,
                ], 200);
            }

            // Determine label for frontend
            $appointmentDate = date('Y-m-d', strtotime($nextAppointment->start_date));
            $today = now()->toDateString();
            $label = ($appointmentDate === $today) ? 'Current Appointment' : 'Next Appointment';

            // Return appointment details and label
            return response()->json([
                'message' => 'Next appointment fetched successfully.',
                'appointment' => [
                    'id' => $nextAppointment->uuid,
                    'start_date' => $nextAppointment->start_date,
                    'label' => $label,
                    'status' => $nextAppointment->status,
                    'start_time' => $nextAppointment->start_time,
                    'start_time_period' => $nextAppointment->start_time_period,
                ],
            ], 200);

        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('Getting next appointment failed', [
                'user_id' => Auth::user()->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            // Return error response
            return response()->json([
                'message' => app()->environment('production')
                    ? 'Something went wrong. Please try again later.'
                    : $e->getMessage(),
            ], 500);
        }
    }



    public function getVerifiedHealthWorkers(){
        try {
            // Get the currently authenticated user
            $user = Auth::user();

            // Optimize: Select only needed fields and use proper where clauses for indexes
            $verifiedHealthWorkers = User::select([
                'uuid', 'name', 'image', 'practitioner', 'gender'
            ])
            ->where('role', 'healthworker')
            ->whereNotNull('email_verified_at')
            ->with(['healthworkerReviews' => function($query) {
                $query->select('healthworker_uuid', 'rating');
            }])
            ->get();

            // Format the response: only uuid, image, star rating, name, practitioner, gender, and review ratings
            $result = $verifiedHealthWorkers->map(function($hw) {
                // Calculate average star rating from reviews
                $ratings = $hw->healthworkerReviews->pluck('rating');
                $starRating = $ratings->count() > 0 ? round($ratings->avg(), 1) : null;
                return [
                    'uuid' => $hw->uuid,
                    'image' => $hw->image,
                    'star_rating' => $starRating,
                    'name' => $hw->name,
                    'practitioner' => $hw->practitioner,
                    'gender' => $hw->gender,
                    'reviews' => $ratings->all(), // Only ratings
                ];
            });

            // Return the list of verified health workers
            return response()->json([
                'message' => 'Verified health workers fetched successfully.',
                'verified_health_workers' => $result,
            ], 200);

        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('Getting verified health workers failed', [
                'user_id' => Auth::user()->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            // Return error response
            return response()->json([
                'message' => app()->environment('production')
                    ? 'Something went wrong. Please try again later.'
                    : $e->getMessage(),
            ], 500);
        }
    }


}
