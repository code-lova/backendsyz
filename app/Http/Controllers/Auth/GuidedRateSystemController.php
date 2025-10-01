<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\GuidedRateSystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GuidedRateSystemController extends Controller
{
    public function show(Request $request)
    {
        try {
            $user = $request->user();

            $grs = GuidedRateSystem::with('serviceTypes')->where('user_uuid', $user->uuid)->first();

            if (!$grs) {
                return response()->json([
                    'message' => 'No guided rate found',
                    'grs' => null
                ], 200);
            }

            return response()->json([
                'grs' => [
                    'id' => $grs->uuid,
                    'userId' => $grs->user_uuid,
                    'rate_type' => $grs->rate_type,
                    'nurse_type' => $grs->nurse_type,
                    'guided_rate' => $grs->guided_rate,
                    'care_duration' => $grs->care_duration,
                    'service_types' => $grs->serviceTypes->pluck('service_type')->toArray(),
                    'guided_rate_justification' => $grs->guided_rate_justification,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get guided rate system', [
                'user_uuid' => optional(Auth::user())->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $message = app()->environment('production')
                ? 'Something went wrong while fetching guided rate'
                : $e->getMessage();

            return response()->json([
                'message' => $message,
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Update existing GuidedRateSystem
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'rate_type' => 'required|in:shift,hourly',
            'nurse_type' => 'nullable|string',
            'guided_rate' => 'required|integer',
            'care_duration' => 'required|string',
            'guided_rate_justification' => 'nullable|string|max:1000',
            'service_types' => 'required|array',
            'service_types.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        DB::beginTransaction();

        try {
            $grs = GuidedRateSystem::where('user_uuid', $user->uuid)->first();

            if (!$grs) {
                // Create the guided rate system
                $grs = GuidedRateSystem::create([
                    'user_uuid' => $user->uuid,
                    'rate_type' => $data['rate_type'],
                    'nurse_type' => $data['nurse_type'],
                    'guided_rate' => $data['guided_rate'],
                    'care_duration' => $data['care_duration'],
                    'guided_rate_justification' => $data['guided_rate_justification'] ?? null,
                ]);
            } else {
                // Update existing guided rate
                $grs->update([
                    'rate_type' => $data['rate_type'],
                    'nurse_type' => $data['nurse_type'],
                    'guided_rate' => $data['guided_rate'],
                    'care_duration' => $data['care_duration'],
                    'guided_rate_justification' => $data['guided_rate_justification'] ?? null,
                ]);

                // Remove old services
                $grs->serviceTypes()->delete();
            }

            // Insert new service types (both for create and update)
            foreach ($data['service_types'] as $service) {
                $grs->serviceTypes()->create([
                    'service_type' => $service,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Guided rate saved successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GRS update failed', [
                'user_id' => optional(Auth::user())->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $message = app()->environment('production')
            ? 'Something Went Wrong'
            : $e->getMessage();

            return response()->json([
                'message' => $message,
                'error' => $e->getMessage()
            ], 500);
        }
    }






}
