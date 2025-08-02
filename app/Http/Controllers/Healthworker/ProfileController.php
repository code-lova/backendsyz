<?php

namespace App\Http\Controllers\Healthworker;

use App\Http\Controllers\Controller;
use App\Http\Resources\HealthWorkerResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{

    //Geting Healhworkder details
    public function authHealthDetails(Request $request){
        $user = Auth::user();
        return new HealthWorkerResource($user);
    }


    public function update(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'phone' => ['required', 'regex:/^\+?[0-9]{10,15}$/', 'unique:users,phone,' . $user->id],
                'date_of_birth' => 'required|date',
                'country' => 'required|string|max:255',
                'region' => 'required|string',
                'working_hours' => [
                    'required',
                    'string',
                    'regex:/^(0?[1-9]|1[0-2])(:[0-5][0-9])?(am|pm)\s*-\s*(0?[1-9]|1[0-2])(:[0-5][0-9])?(am|pm)$/i'
                ],
                'address' => 'required|string',
                'religion' => 'nullable|string',
                'gender' => 'required|in:Male,Female,Non-binary,Transgender,Bigender',
                'about' => 'required|string|max:1000',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ], [
                'email.unique' => 'This email is already in use.',
                'phone.unique' => 'This phone number is already registered.',
                'about.max' => 'About section must not exceed 1000 characters.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();


            /** @var \App\Models\User $user */
            $user->update($validated);

            DB::commit();

            return response()->json([
                'message' => 'Profile updated successfully.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User update failed', [
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
