<?php

namespace App\Http\Controllers\Healthworker;

use App\Http\Controllers\Controller;
use App\Models\UnavailableDates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UnavailableDateController extends Controller
{
    public function toggle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $date = $request->input('date');

        try {
            $response = null;

            DB::transaction(function () use ($user, $date, &$response) {
                $existing = UnavailableDates::where('user_uuid', $user->uuid)
                    ->where('date', $date)
                    ->first();

                if ($existing) {
                    $existing->delete();

                    $response = response()->json([
                        'message' => 'Date removed from unavailable list',
                        'date'    => $date,
                        'status'  => 'removed',
                    ], 200);
                } else {
                    UnavailableDates::create([
                        'user_uuid' => $user->uuid,
                        'date'      => $date,
                    ]);

                    $response = response()->json([
                        'message' => 'Date added to unavailable list',
                        'date'    => $date,
                        'status'  => 'added',
                    ], 201);
                }
            });

            return $response;

        } catch (\Exception $e) {
            Log::error('Unavailable date toggle failed', [
                'user_id' => optional(Auth::user())->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => app()->environment('production')
                    ? 'Something went wrong.'
                    : $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function getUnavailableDates(Request $request)
    {
        try {
            $user = $request->user();

            $dates = UnavailableDates::where('user_uuid', $user->uuid)->pluck('date'); // only get the dates

            return response()->json($dates, 200);
        } catch (\Exception $e) {
            Log::error('Fetching unavailable dates failed', [
                'user_id' => optional($request->user())->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to fetch unavailable dates',
                'error' => app()->environment('production') ? null : $e->getMessage()
            ], 500);
        }
    }


}
