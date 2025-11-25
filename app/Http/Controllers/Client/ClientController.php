<?php

namespace App\Http\Controllers\Client;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ClientResource;
use Illuminate\Support\Facades\Schema;
use App\Http\Requests\UpdateClientProfileRequest;



class ClientController extends Controller
{
    public function getClientDetails(Request $request){

        $user = Auth::user();

        return new ClientResource($user);
    }

    public function updateClientProfile(UpdateClientProfileRequest $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            Log::info('Client profile update initiated', [
                'user_id' => $user->id,
                'user_uuid' => $user->uuid,
                'request_data' => $request->validated(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            // Validate that the user exists and is active
            if (!$user || $user->is_active === '0') {
                Log::warning('Profile update attempted by inactive user', [
                    'user_id' => $user->id ?? 'unknown',
                    'is_active' => $user->is_active ?? 'unknown',
                ]);

                return response()->json([
                    'message' => 'Account is not active.',
                ], 403);
            }

            // Start database transaction
            DB::beginTransaction();

            // Update user profile
            $updateData = $request->validated();
            $user->update($updateData);

            // Log successful update
            Log::info('Client profile updated successfully', [
                'user_id' => $user->id,
                'user_uuid' => $user->uuid,
                'updated_fields' => array_keys($updateData),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Profile updated successfully.',
                'data' => [
                    'user' => [
                        'id' => $user->uuid,
                        'name' => $user->name,
                        'email' => $user->email,
                    ]
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            Log::warning('Client profile update validation failed', [
                'user_id' => optional(Auth::user())->id,
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            Log::error('Database error during client profile update', [
                'user_id' => optional(Auth::user())->id,
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'sql_state' => $e->errorInfo[0] ?? null,
            ]);

            // Check for common database issues
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                return response()->json([
                    'message' => 'Email or phone number already exists.',
                ], 422);
            }

            return response()->json([
                'message' => app()->environment('production')
                    ? 'Database error occurred. Please try again later.'
                    : 'Database error: ' . $e->getMessage(),
            ], 500);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Unexpected error during client profile update', [
                'user_id' => optional(Auth::user())->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => app()->environment('production')
                    ? 'Something went wrong. Please try again later.'
                    : $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Debug endpoint to help troubleshoot profile update issues
     */
    public function debugProfileUpdate(Request $request)
    {
        try {
            $user = Auth::user();

            return response()->json([
                'message' => 'Debug information for profile update',
                'data' => [
                    'user_info' => [
                        'id' => $user->id,
                        'uuid' => $user->uuid,
                        'email' => $user->email,
                        'name' => $user->name,
                        'is_active' => $user->is_active,
                        'role' => $user->role,
                    ],
                    'request_info' => [
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->header('User-Agent'),
                        'content_type' => $request->header('Content-Type'),
                        'method' => $request->method(),
                    ],
                    'database_info' => [
                        'connection' => config('database.default'),
                        'table_exists' => Schema::hasTable('users'),
                    ],
                    'validation_rules' => (new UpdateClientProfileRequest())->rules(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Debug profile update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Debug failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


}
