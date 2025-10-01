<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSession;
use App\Models\BookingAppt;
use App\Models\SupportMessage;
use App\Models\UnavailableDates;
use App\Models\GuidedRateSystem;
use App\Models\UserSettings;
use App\Models\DeletedAccounts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class UserController extends Controller
{
    /**
     * Permanently delete the authenticated user's account and all associated data.
     *
     * This operation:
     * 1. Creates a record in deleted_accounts table for audit purposes
     * 2. Logs out the user from all devices
     * 3. Deletes all user data from related tables
     * 4. Permanently removes the user account
     *
     * @param Request $request - Must contain 'confirmation' => 'DELETE_MY_ACCOUNT'
     *                          - Optional 'reasons_for_deletion' => string (max 1000 chars)
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteUserAccount(Request $request)
    {
        // Validate confirmation and optional deletion reason
        $request->validate([
            'confirmation' => 'required|string|in:DELETE_MY_ACCOUNT',
            'reasons_for_deletion' => 'required|string|max:1000',
        ], [
            'confirmation.in' => 'You must confirm account deletion by typing "DELETE_MY_ACCOUNT"',
            'reasons_for_deletion.max' => 'Deletion reason cannot exceed 1000 characters.',
        ]);

        try {

            /** @var \App\Models\User $user */
            $user = Auth::user();
            $userUuid = $user->uuid;

            Log::info('User account deletion initiated', [
                'user_id' => $user->id,
                'user_uuid' => $userUuid,
                'email' => $user->email,
            ]);

            DB::beginTransaction();

            // Step 1: Create record in deleted_accounts table before deletion
            $deletedAccountRecord = DeletedAccounts::create([
                'user_uuid' => $userUuid,
                'fullname' => $user->fullname ?? $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'reasons_for_deletion' => $request->input('reasons_for_deletion'),
            ]);

            Log::info('Deleted account record created', [
                'deleted_account_id' => $deletedAccountRecord->id,
                'user_uuid' => $userUuid,
            ]);

            // Step 3: Logout from all devices first (revoke all tokens and expire sessions)
            // Instead of instantiating AuthController directly, we'll revoke tokens manually
            // since AuthController now has dependencies that would require injection

            // Revoke all personal access tokens for this user
            $user->tokens()->delete();

            // Mark all user sessions as expired
            UserSession::where('user_uuid', $userUuid)->update([
                'expired_at' => now(),
                'is_active' => false
            ]);

            Log::info('User logged out from all devices', [
                'user_uuid' => $userUuid,
                'tokens_revoked' => 'all',
                'sessions_expired' => 'all'
            ]);

            // Step 4: Delete from all related tables containing user_uuid

            // Delete user sessions (should already be handled by logout, but ensuring cleanup)
            $userSessionsDeleted = UserSession::where('user_uuid', $userUuid)->delete();
            Log::info('User sessions deleted', ['count' => $userSessionsDeleted]);

            // Delete booking appointments
            $bookingApptsDeleted = BookingAppt::where('user_uuid', $userUuid)->delete();
            Log::info('Booking appointments deleted', ['count' => $bookingApptsDeleted]);

            // Delete support messages
            $supportMessagesDeleted = SupportMessage::where('user_uuid', $userUuid)->delete();
            Log::info('Support messages deleted', ['count' => $supportMessagesDeleted]);

            // Delete unavailable dates
            $unavailableDatesDeleted = UnavailableDates::where('user_uuid', $userUuid)->delete();
            Log::info('Unavailable dates deleted', ['count' => $unavailableDatesDeleted]);

            // Delete guided rate systems
            $guidedRateSystemsDeleted = GuidedRateSystem::where('user_uuid', $userUuid)->delete();
            Log::info('Guided rate systems deleted', ['count' => $guidedRateSystemsDeleted]);

            // Delete user settings
            $userSettingsDeleted = UserSettings::where('user_uuid', $userUuid)->delete();
            Log::info('User settings deleted', ['count' => $userSettingsDeleted]);

            // Delete healthworker reviews (both as client and as healthworker)
            $clientReviewsDeleted = DB::table('healthworker_reviews')
                ->where('client_uuid', $userUuid)
                ->delete();
            Log::info('Client reviews deleted', ['count' => $clientReviewsDeleted]);

            $healthworkerReviewsDeleted = DB::table('healthworker_reviews')
                ->where('healthworker_uuid', $userUuid)
                ->delete();
            Log::info('Healthworker reviews deleted', ['count' => $healthworkerReviewsDeleted]);

            // Delete from client support tickets (checking if this table exists)
            $clientSupportTicketsDeleted = DB::table('client_support_tickets')
                ->where('user_uuid', $userUuid)
                ->delete();
            Log::info('Client support tickets deleted', ['count' => $clientSupportTicketsDeleted]);

            // Delete personal access tokens (Sanctum tokens) - using polymorphic relationship
            $personalAccessTokensDeleted = DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->where('tokenable_id', $user->id)
                ->delete();
            Log::info('Personal access tokens deleted', ['count' => $personalAccessTokensDeleted]);

            // Delete any tokens with user_session_uuid relationship
            $sessionTokensDeleted = DB::table('personal_access_tokens')
                ->whereIn('user_session_uuid', function($query) use ($userUuid) {
                    $query->select('uuid')
                          ->from('user_sessions')
                          ->where('user_uuid', $userUuid);
                })
                ->delete();
            Log::info('Session-related tokens deleted', ['count' => $sessionTokensDeleted]);

            // Step 5: Delete the user record itself
            $userDeleted = $user->delete();

            if (!$userDeleted) {
                throw new Exception('Failed to delete user record');
            }

            DB::commit();

            Log::info('User account successfully deleted', [
                'user_id' => $user->id,
                'user_uuid' => $userUuid,
                'email' => $user->email,
                'deleted_account_record_id' => $deletedAccountRecord->id,
                'summary' => [
                    'user_sessions' => $userSessionsDeleted,
                    'booking_appointments' => $bookingApptsDeleted,
                    'support_messages' => $supportMessagesDeleted,
                    'unavailable_dates' => $unavailableDatesDeleted,
                    'guided_rate_systems' => $guidedRateSystemsDeleted,
                    'user_settings' => $userSettingsDeleted,
                    'client_reviews' => $clientReviewsDeleted,
                    'healthworker_reviews' => $healthworkerReviewsDeleted,
                    'client_support_tickets' => $clientSupportTicketsDeleted,
                    'personal_access_tokens' => $personalAccessTokensDeleted,
                    'session_tokens' => $sessionTokensDeleted,
                ]
            ]);

            return response()->json([
                'message' => 'Your account has been permanently deleted. We\'re sorry to see you go.',
                'deleted_at' => now()->toISOString(),
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('User account deletion failed', [
                'user_id' => Auth::user()->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => app()->environment('production')
                    ? 'Account deletion failed. Please try again later or contact support.'
                    : $e->getMessage(),
            ], 500);
        }
    }
}
