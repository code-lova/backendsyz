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

            // Fetch all bookings for this user in the selected month/year
            // Assumes booking_appts table has user_uuid and created_at fields
            $bookings = DB::table('booking_appts')
                ->where('user_uuid', $user->uuid)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->select('id', 'created_at')
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
            // Get the currently authenticated user
            $user = Auth::user();

            // Count total bookings for this user
            // Assumes booking_appts table has user_uuid field
            $totalAppointments = DB::table('booking_appts')
                ->where('user_uuid', $user->uuid)
                ->count();

            // Return the total count as JSON
            return response()->json([
                'message' => 'Total appointments fetched successfully.',
                'total_appointments' => $totalAppointments,
            ], 200);

        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('Getting total appointments failed', [
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


    public function getNextAppointment(){
        try {
            // Get the currently authenticated user
            $user = Auth::user();

            // Fetch the next upcoming appointment for the user
            // Assumes booking_appts table has user_uuid and booking_date fields
            $nextAppointment = DB::table('booking_appts')
                ->where('user_uuid', $user->uuid)
                ->whereDate('start_date', '>=', now()->toDateString())
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

            // Fetch verified health workers using Eloquent, eager load reviews
            $verifiedHealthWorkers = User::whereNotNull('email_verified_at')
                ->where('email_verified_at', '!=', '')
                ->where('role', 'healthworker')
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
