<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingAppt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserManagementController extends Controller
{
    public function listUsers(){
        try {
            $query = User::query();

            // Search by name or email
            if (request()->has('search') && request('search')) {
                $search = request('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%");
                });
            }

            // Filter by role
            if (request()->has('role') && request('role')) {
                $query->where('role', request('role'));
            }

            // Filter by email verified status
            if (request()->has('email_verified') && request('email_verified') !== null) {
                $verified = filter_var(request('email_verified'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($verified === true) {
                    $query->whereNotNull('email_verified_at');
                } elseif ($verified === false) {
                    $query->whereNull('email_verified_at');
                }
            }

            // Sort by role or name
            $sortBy = request('sort_by') ?? 'created_at';
            $sortOrder = strtolower(request('sort_order') ?? 'desc'); // default desc (newest first)
            $allowedSortFields = ['role', 'name', 'email_verified_at', 'created_at'];
            $allowedSortOrders = ['asc', 'desc'];
            if (in_array($sortBy, $allowedSortFields) && in_array($sortOrder, $allowedSortOrders)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $users = $query->get();

            return response()->json([
                'status' => 'success',
                'users' => $users
            ], 200);
        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('fetching all users failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => app()->environment('production') ? 'Something went wrong.' : $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update user details
     *
     * @param \Illuminate\Http\Request $request
     * @param string $uuid User's UUID
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUser(Request $request, string $uuid)
    {
        try {
            // Validate the UUID format
            $validator = Validator::make(['uuid' => $uuid], [
                'uuid' => 'required|uuid'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Invalid user identifier provided.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get authenticated admin user
            $admin = Auth::user();
            if (!$admin || $admin->role !== 'admin') {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Unauthorized. Only admins can update user details.'
                ], 403);
            }

            // Find user by UUID
            $user = User::where('uuid', $uuid)->first();

            if (!$user) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'User not found.'
                ], 404);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'role' => 'nullable|in:client,healthworker,admin',
                'practitioner' => 'nullable|max:255',
                'phone' => 'nullable|regex:/^\+?[0-9]{10,15}$/|unique:users,phone,' . $user->id,
                'gender' => 'nullable|string|in:Male,Female,Non-binary,Transgender,Bigender',
                'country' => 'nullable|string|max:100',
                'region' => 'nullable|string|max:100',
                'religion' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:255',
                'about' => 'nullable|string|max:1000',
                'two_factor_enabled' => 'nullable|boolean',
            ], [
                'name.required' => 'User name is required.',
                'name.max' => 'User name must not exceed 255 characters.',
                'email.required' => 'Email address is required.',
                'email.email' => 'Please provide a valid email address.',
                'email.unique' => 'This email address is already in use by another user.',
                'role.in' => 'Invalid user role specified.',
                'phone.regex' => 'Please provide a valid phone number.',
                'phone.unique' => 'This phone number is already in use by another user.',
                'gender.in' => 'Invalid gender option selected.',
                'about.max' => 'About section must not exceed 1000 characters.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Store original data for logging
            $originalData = $user->only(array_keys($validated));

            // Use database transaction for data integrity
            DB::transaction(function () use ($user, $validated) {
                $user->update($validated);
            });

            // Log the update action for audit trail
            Log::info('Admin user update action', [
                'admin_id' => $admin->id,
                'admin_uuid' => $admin->uuid,
                'admin_email' => $admin->email,
                'target_user_id' => $user->id,
                'target_user_uuid' => $user->uuid,
                'target_user_email' => $user->email,
                'original_data' => $originalData,
                'updated_data' => $validated,
                'timestamp' => now()
            ]);

            return response()->json([
                'status' => 'Success',
                'message' => 'User updated successfully.',
                'data' => [
                    'user' => [
                        'uuid' => $user->uuid,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'phone' => $user->phone,
                        'gender' => $user->gender,
                        'country' => $user->country,
                        'region' => $user->region,
                        'address' => $user->address,
                        'about' => $user->about,
                        'is_active' => $user->is_active == '1', // Convert to boolean for API response
                        'updated_at' => $user->updated_at
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('Unable to update user details', [
                'admin_id' => Auth::id(),
                'admin_uuid' => Auth::user()->uuid ?? null,
                'target_user_uuid' => $uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'Error',
                'message' => app()->environment('production') 
                    ? 'Something went wrong while updating user details.' 
                    : $e->getMessage(),
                'error' => !app()->environment('production') ? $e->getMessage() : null
            ], 500);
        }
    }

    //fetch all users with role as health worker and their guided rate system and unavailable dates
    public function getHealthWorkers()
    {
        try {
            // Eager load guidedRateSystem relationship and service types
            $healthWorkers = User::where('role', 'healthworker')
                ->with('guidedRateSystem.serviceTypes')
                ->with('unavailableDates')
                ->latest()
                ->get();

            // Check if health worker is assigned to confirmed/ongoing appointments
            $healthWorkers->each(function ($healthWorker) {
                $healthWorker->is_assigned = BookingAppt::where('health_worker_uuid', $healthWorker->uuid)
                    ->whereIn('status', ['Confirmed', 'Ongoing'])
                    ->exists();
                    
                // Check if health worker is assigned to processing appointments (awaiting confirmation)
                $healthWorker->is_processing = BookingAppt::where('health_worker_uuid', $healthWorker->uuid)
                    ->where('status', 'Processing')
                    ->exists();
            });

            return response()->json([
                'status' => 'success',
                'health_workers' => $healthWorkers
            ], 200);
        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('Fetching health workers failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => app()->environment('production') ? 'Something went wrong.' : $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Block or unblock a user account
     *
     * @param \Illuminate\Http\Request $request
     * @param string $uuid User's UUID
     * @return \Illuminate\Http\JsonResponse
     */
    public function blockUser(Request $request, string $uuid)
    {
        try {
            // Validate the UUID format
            $validator = Validator::make(['uuid' => $uuid], [
                'uuid' => 'required|uuid'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Invalid user identifier provided.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get authenticated admin user
            $admin = Auth::user();
            if (!$admin || $admin->role !== 'admin') {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Unauthorized. Only admins can block/unblock users.'
                ], 403);
            }

            // Find user by UUID
            $user = User::where('uuid', $uuid)->first();

            if (!$user) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'User not found.'
                ], 404);
            }

            // Prevent admin from blocking themselves
            if ($user->uuid === $admin->uuid) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'You cannot block your own account.'
                ], 400);
            }

            // Prevent blocking other admins (optional business logic)
            if ($user->role === 'admin') {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Admin accounts cannot be blocked.'
                ], 400);
            }

            // Store the current status for logging
            $previousStatus = $user->is_active;

            // Use database transaction for data integrity
            DB::transaction(function () use ($user) {
                // Toggle block status - convert boolean to string for ENUM column
                $user->is_active = $user->is_active == '1' ? '0' : '1';
                $user->save();
            });

            // Determine action and message
            $action = $user->is_active == '1' ? 'unblocked' : 'blocked';
            $message = $user->is_active == '1' ? 'User has been unblocked successfully.' : 'User has been blocked successfully.';

            // Log the action for audit trail
            Log::info('Admin user block/unblock action', [
                'admin_id' => $admin->id,
                'admin_uuid' => $admin->uuid,
                'admin_email' => $admin->email,
                'target_user_id' => $user->id,
                'target_user_uuid' => $user->uuid,
                'target_user_email' => $user->email,
                'target_user_role' => $user->role,
                'action' => $action,
                'previous_status' => $previousStatus == '1' ? 'active' : 'blocked',
                'new_status' => $user->is_active == '1' ? 'active' : 'blocked',
                'timestamp' => now()
            ]);

            return response()->json([
                'status' => 'Success',
                'message' => $message,
                'data' => [
                    'user' => [
                        'uuid' => $user->uuid,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'is_active' => $user->is_active == '1', // Convert to boolean for API response
                        'updated_at' => $user->updated_at
                    ],
                    'action_performed' => $action
                ]
            ], 200);

        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('Unable to block/unblock user', [
                'admin_id' => Auth::id(),
                'admin_uuid' => Auth::user()->uuid ?? null,
                'target_user_uuid' => $uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'Error',
                'message' => app()->environment('production') 
                    ? 'Something went wrong while updating user status.' 
                    : $e->getMessage(),
                'error' => !app()->environment('production') ? $e->getMessage() : null
            ], 500);
        }
    }
}