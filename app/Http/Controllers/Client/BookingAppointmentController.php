<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\BookingAppt;
use App\Models\BookingApptOthers;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BookingAppointmentController extends Controller
{

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

            'special_notes' => 'required|string|min:20|max:1000',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required|date_format:H:i,G:i',
            'end_time' => 'required|date_format:H:i,G:i|after:start_time',
            'start_time_period' => 'required|string|in:AM,PM',
            'end_time_period' => 'required|string|in:AM,PM',

            'medical_services' => 'required|array',
            'medical_services.*' => 'string|max:255',
            'other_extra_service' => 'nullable|array',
            'other_extra_service.*' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if (BookingAppt::where('user_uuid', $user->uuid)->where('status', 'Pending')->exists()) {
            return response()->json([
                'message' => 'You have an existing pending booking.',
                'errors' => ['Your exixting appointment is not completed.']
            ], 409);
        }

        try {

            DB::beginTransaction();

            $bookingApt = BookingAppt::create(array_merge(
                ['user_uuid' => $user->uuid],
                Arr::only($data, [
                    'requesting_for', 'someone_name', 'someone_phone', 'someone_email',
                    'other_medical_information', 'care_duration', 'care_duration_value',
                    'care_type', 'accommodation', 'meal', 'num_of_meals',
                    'special_notes', 'start_date', 'end_date', 'start_time', 'end_time', 'status'
                ])
            ));

            // Insert new Booking Appt others if applicable
            //Removed redundant json_encode() since Laravel will cast arrays automatically via $casts.
            //in model
            $bookingApt->others()->create([
                'medical_services' => $data['medical_services'],
                'other_extra_service' => $data['other_extra_service'] ?? [],
            ]);

            // TODO:: Dont forget to send emails to admin and client

            DB::commit();

            return response()->json([
                'status' => 'Success',
                'message' => 'Booking created successfully.',
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
}
