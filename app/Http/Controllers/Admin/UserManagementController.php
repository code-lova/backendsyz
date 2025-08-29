<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingAppt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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


    public function updateUser(Request $request, $id){
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'role' => 'sometimes|in:client,healthworker,admin',
                'practitioner' => 'sometimes|max:255',
                'phone' => 'sometimes|regex:/^\+?[0-9]{10,15}$/|unique:users,phone,' . $user->id,
                'gender' => 'sometimes',
                'country' => 'sometimes|max:100',
                'region' => 'sometimes|max:100',
                'religion' => 'sometimes|max:100',
                'address' => 'sometimes|max:255',
                'about' => 'sometimes|max:1000',
                'two_factor_enabled' => 'sometimes|boolean',
                
            ]);

            // Update user with validated data
            $user->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'User updated successfully.',
                'user' => $user
            ], 200);

        } catch (\Exception $e) {
             // Log error for debugging
            Log::error('Unable to update user details', [
                'user_id' => $user->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => app()->environment('production') ? 'Something went wrong.' : $e->getMessage(),
                'error' => $e->getMessage()
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
}
