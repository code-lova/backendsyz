<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\TwoFactorCodeMail;
use App\Models\User;
use App\Models\UserSession;
use App\Models\UserSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SettingsController extends Controller
{

    //Enable user 2fa
    public function enable2FA(Request $request)
    {
        try {
            $user = $request->user(); // ✅ Always use the authenticated user

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized.'
                ], 401);
            }

            // Extra safety: prevent re-enabling if already enabled
            if ($user->two_factor_enabled) {
                return response()->json([
                    'message' => '2FA is already enabled.'
                ], 400);
            }

            // Enable 2FA (clear any old codes)
            $user->forceFill([
                'two_factor_enabled'    => true,
                'two_factor_code'       => null,
                'two_factor_expires_at' => null,
            ])->save();

            return response()->json([
                'message' => '2FA has been enabled successfully.'
            ], 200);

        }catch (\Exception $e) {
            Log::error('Enable 2FA error', [
                'user_id' => Auth::user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => app()->environment('production')
                    ? 'Something went wrong. Please try again later.'
                    : $e->getMessage(),
            ], 500);
        }
    }


    //Disable 2fa
    public function disable2FA(Request $request)
    {
        try{
            // Validate that password is provided
            $request->validate([
                'password' => 'required|string|min:6',
            ], [
                'password.required' => 'Password is required to disable 2FA.',
                'password.min' => 'Password must be at least 6 characters.',
            ]);

            $user = $request->user(); // ✅ Always use the authenticated user

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized.'
                ], 401);
            }

            // Extra safety: prevent disabling if already disabled
            if (!$user->two_factor_enabled) {
                return response()->json([
                    'message' => '2FA is already disabled.'
                ], 400);
            }

            // Verify the current password before disabling 2FA
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'The provided password is incorrect.'
                ], 422);
            }

            $user->forceFill([
                'two_factor_enabled' => false,
                'two_factor_code'    => null,
                'two_factor_expires_at' => null,
            ])->save();

            Log::info('2FA disabled successfully', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);

            return response()->json([
                'message' => '2FA has been disabled successfully.'
            ], 200);


        }catch (\Exception $e) {
            Log::error('Disable 2FA error', [
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

    //Get uses session
    public function getUserSessions(){
        try{

            $user = Auth::user();

            $sessions = UserSession::where('user_uuid', $user->uuid)
                                    ->where('status', 'active')
                                    ->latest()
                                    ->get();

            if($sessions->isEmpty()){
                return response()->json([
                    'message' => 'No active session found.'
                ], 404);
            }

            // Transform the collection to return all sessions
            $sessionData = $sessions->map(function($session) {
                return [
                    'id' => $session->uuid,
                    'ip_address' => $session->ip_address,
                    'device' => $session->device,
                    'platform' => $session->platform,
                    'browser' => $session->browser,
                    'status' => $session->status,
                    'last_used_at' => $session->last_used_at,
                    'created_at' => $session->created_at,
                ];
            });

            return response()->json([
                'message' => 'Active sessions retrieved successfully.',
                'data' => $sessionData
            ], 200);

        }catch (\Exception $e) {
            Log::error('Get user sessions error', [
                'user_id' => Auth::user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => app()->environment('production')
                    ? 'Something went wrong. Please try again later.'
                    : $e->getMessage(),
            ], 500);
        }
    }


    public function updateSettings(Request $request)
    {
        try{

            $user = $request->user();

            $user->settings()->updateOrCreate(
                ['user_uuid' => $user->uuid],
                $request->only([
                    'email_notifications',
                    'push_notifications',
                    'booking_reminders',
                    'marketing_updates',
                    'profile_visibility',
                    'activity_status',
                    'data_collection',
                    'third_party_cookies'
                ])
            );

            return response()->json([
                'message' => 'Settings updated successfully.'
            ], 200);

        }catch (\Exception $e) {
            Log::error('Update settings error', [
                'user_id' => Auth::user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => app()->environment('production')
                    ? 'Something went wrong. Please try again later.'
                    : $e->getMessage(),
            ], 500);
        }
    }


    public function getUserSettings(Request $request){
        try{

            $user = $request->user();

            // Get user settings or return default values if none exist
            $settings = $user->settings()->first();

            if (!$settings) {
                // Return default settings structure if no settings exist
                return response()->json([
                    'message' => 'User settings retrieved successfully.',
                    'data' => [
                        'email_notifications' => true,
                        'push_notifications' => true,
                        'booking_reminders' => true,
                        'marketing_updates' => false,
                        'profile_visibility' => true,
                        'activity_status' => true,
                        'data_collection' => false,
                        'third_party_cookies' => false,
                    ]
                ], 200);
            }

            return response()->json([
                'message' => 'User settings retrieved successfully.',
                'data' => [
                    'email_notifications' => $settings->email_notifications ?? true,
                    'push_notifications' => $settings->push_notifications ?? true,
                    'booking_reminders' => $settings->booking_reminders ?? true,
                    'marketing_updates' => $settings->marketing_updates ?? false,
                    'profile_visibility' => $settings->profile_visibility ?? true,
                    'activity_status' => $settings->activity_status ?? true,
                    'data_collection' => $settings->data_collection ?? false,
                    'third_party_cookies' => $settings->third_party_cookies ?? false,
                ]
            ], 200);

        }catch (\Exception $e) {
            Log::error('Get user settings error', [
                'user_id' => Auth::user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => app()->environment('production')
                    ? 'Something went wrong. Please try again later.'
                    : $e->getMessage(),
            ], 500);
        }
    }


}
