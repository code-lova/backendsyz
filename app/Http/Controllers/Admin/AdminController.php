<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminResource;
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
}
