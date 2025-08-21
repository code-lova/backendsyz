<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\TwoFactorCodeMail;
use App\Mail\VerifyEmailCodeMail;
use App\Models\User;
use Exception;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Jenssegers\Agent\Facades\Agent;

class AuthController extends Controller
{


      // Register API
    public function register(Request $request){
        try{

            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:200|unique:users,name',
                'phone' => 'required|regex:/^\+?[0-9]{10,15}$/|unique:users,phone',
                'email' => 'required|email|max:200|unique:users,email',
                'password' => 'required|min:8|confirmed',
                'role' => 'required|in:admin,client,healthworker',
                'practitioner' => 'nullable|in:doctor,nurse,physician_assistant',
            ], [
                'email.required' => 'Email is required',
                'password.required' => 'Provide your password',
                'phone.regex' => 'Mobile number is not valid',
                'phone.required' => 'Mobile number is required',
            ]);

            if($validator->fails()){
                return response()->json([
                    'errors' => $validator->errors()->first()
                ], 422);
            }
            $verificationCode = strtoupper(Str::random(6)); // 6-char alphanumeric

            $user = User::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'role' => $request->role,
                'practitioner' => $request->practitioner,
                'password' => Hash::make($request->password),
                'email_verification_code' => $verificationCode,
                'email_verification_code_expires_at' => now()->addMinutes(10),
            ]);

            $token = $user->createToken('API Token')->plainTextToken;

            // Send email
            if($user->email){
                Mail::to($user->email)->send(new VerifyEmailCodeMail($user));
            }

            DB::commit();

            return response()->json([
                'message' => 'User registered. Please verify your email.',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token
            ], 201);

        }catch(\Throwable $e){
            // Log the exception message along with the stack trace for better debugging
            DB::rollBack();
            Log::error('Registration Error: '.$e->getMessage(), ['exception' => $e]);
            $message = app()->environment('production')
                ? 'Something Went Wrong'
                : $e->getMessage();
            return response()->json([
                'message' => $message
            ], 500);
        }
    }



    // Login API
    public function login(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()->first()
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Invalid credentials'
                ], 401);
            }

             // If 2FA is enabled, send OTP and stop here (no token yet)
            if ($user->two_factor_enabled) {
                // 1) Generate + hash OTP (store hash, email raw)
                $otp = (string) random_int(100000, 999999);

                $user->forceFill([
                    'two_factor_code'        => Hash::make($otp),    // store hash
                    'two_factor_expires_at'  => now()->addMinutes(10),
                ])->save();

                // 2) Email the OTP (queue in production)
                Mail::to($user->email)->send(new TwoFactorCodeMail($user, $otp));

                return response()->json([
                    'requires_2fa' => true,
                    'message'      => 'OTP sent to your email. Please verify to continue.',
                    'meta'         => [
                        'expires_in_minutes' => 10,
                        'email' => $user->email, // front-end can use this for the verify call
                    ],
                ], 200);
            }

            $abilities = [];
            $tokenName = $user->email.'_Token';

            if ($user->role === 'admin') {
                $abilities = ['server:admin'];
                $tokenName = $user->email.'_AdminToken';
            }elseif($user->role === 'client') {
                $abilities = ['server:client'];
                $tokenName = $user->email.'_ClientToken';
            }elseif($user->role === 'healthworker') {
                $abilities = ['server:healthworker'];
                $tokenName = $user->email.'_HealthWorkerToken';
            }

            // Get device/browser information
            $deviceInfo = $this->getDeviceInfo($request);
            
            $session = $user->sessions()->create([
                'ip_address' => $request->ip(),
                'device' => $deviceInfo['device'],
                'platform' => $deviceInfo['platform'] ?: 'Unknown',
                'browser' => $deviceInfo['browser'] ?: 'Unknown',
                'status' => 'active',
                'last_used_at' => now(),
            ]);

            $token = $user->createToken(
                $tokenName,
                $abilities,
                now()->addDay() // 24-hour expiration
            );

            // ✅ Link token with this session
            $plainTextToken = $token->plainTextToken; // send this to frontend
            $tokenModel = $token->accessToken; // returns token model
            $tokenModel->update([
                'user_session_uuid' => $session->uuid,
            ]);


            // Return JSON response without setting any cookies
            return response()->json([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->uuid,
                    'fullname' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'two_factor_auth' => $user->two_factor_enabled
                ],
                'accessToken' => $plainTextToken,
            ]);

        } catch (\Throwable $e) {
            Log::error('Login Error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json([
                'message' => app()->environment('production') ? 'Login failed' : $e->getMessage()
            ], 500);
        }
    }



      //verify 2fa
    public function verify2FA(Request $request)
    {
        try {

            $request->validate([
                'email' => ['required', 'email'],
                'code'  => ['required', 'digits:6'],
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !$user->two_factor_enabled) {
                return response()->json([
                    'message' => '2FA is not enabled for this account.'
                ], 422);
            }

            if (!$user->two_factor_code || !$user->two_factor_expires_at) {
                return response()->json([
                    'message' => 'No active 2FA challenge. Please login again.'
                ], 422);
            }

            if (now()->greaterThan($user->two_factor_expires_at)) {
                // Optional: clear expired challenge
                $user->forceFill([
                    'two_factor_code'       => null,
                    'two_factor_expires_at' => null,
                ])->save();

                return response()->json(['message' => 'OTP expired. Please login again.'], 401);
            }

            // Compare provided code with hashed code in DB
            if (!Hash::check($request->code, $user->two_factor_code)) {
                return response()->json(['message' => 'Invalid OTP code.'], 401);
            }

            // Clear challenge
            $user->forceFill([
                'two_factor_code'       => null,
                'two_factor_expires_at' => null,
            ])->save();

            // Mint Sanctum token with the same logic as in login
            $abilities = [];
            $tokenName = $user->email . '_Token';

            if ($user->role === 'admin') {
                $abilities = ['server:admin'];
                $tokenName = $user->email . '_AdminToken';
            } elseif ($user->role === 'client') {
                $abilities = ['server:client'];
                $tokenName = $user->email . '_ClientToken';
            } elseif ($user->role === 'healthworker') {
                $abilities = ['server:healthworker'];
                $tokenName = $user->email . '_HealthWorkerToken';
            }

            // Get device/browser information
            $deviceInfo = $this->getDeviceInfo($request);

            // Debug logging to see what Agent is detecting
            Log::info('2FA Agent Detection Debug', $deviceInfo);

            $session = $user->sessions()->create([
                'ip_address' => $request->ip(),
                'device' => $deviceInfo['device'],
                'platform' => $deviceInfo['platform'] ?: 'Unknown',
                'browser' => $deviceInfo['browser'] ?: 'Unknown',
                'status' => 'active',
                'last_used_at' => now(),
            ]);

            $token = $user->createToken(
                $tokenName,
                $abilities,
                now()->addDay() // 24-hour expiration
            );

            // ✅ Link token with this session
            $plainTextToken = $token->plainTextToken; // send this to frontend
            $tokenModel = $token->accessToken; // returns token model
            $tokenModel->update([
                'user_session_uuid' => $session->uuid,
            ]);

            return response()->json([
                'status'  => 'Success',
                'message' => '2FA verification successful.',
                'user' => [
                    'id'       => $user->uuid,
                    'fullname' => $user->name,
                    'email'    => $user->email,
                    'role'     => $user->role,
                    'two_factor_auth' => $user->two_factor_enabled
                ],
                'accessToken' => $plainTextToken,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Login Error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json([
                'message' => app()->environment('production') ? 'Login failed' : $e->getMessage()
            ], 500);
        }
    }


    //resend OTP
    public function resend2FA(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !$user->two_factor_enabled) {
            return response()->json([
                'message' => '2FA is not enabled for this account.'
            ], 422);
        }

        $otp = (string) random_int(100000, 999999);

        $user->forceFill([
            'two_factor_code'       => Hash::make($otp),
            'two_factor_expires_at' => now()->addMinutes(10),
        ])->save();

        Mail::to($user->email)->send(new TwoFactorCodeMail($user, $otp));

        return response()->json([
            'message' => 'A new OTP has been sent to your email.',
            'meta' => ['expires_in_minutes' => 10]
        ]);
    }


    //Updating user location
    public function updateUserLocation(Request $request){
        try{

            DB::beginTransaction();
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
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
                'message' => 'Location updated.',
            ], 200);

        }catch(\Exception $e){
            DB::rollBack();
            Log::error('location update failed', [
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



    //Update image function
    public function updateImage(Request $request){
        try{

            DB::beginTransaction();
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'image' => 'nullable|url',
                'image_public_id' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

             // Handle image replacement
            if (isset($validated['image']) && isset($validated['image_public_id'])) {
                // If user already has image → delete old Cloudinary image
                if ($user->image_public_id) {
                    $this->deleteCloudinaryImage($user->image_public_id);
                }

                $user->image = $validated['image'];
                $user->image_public_id = $validated['image_public_id'];
            }

            // Unset these from validated so no double update
            unset($validated['image'], $validated['image_public_id']);

            /** @var \App\Models\User $user */
            $user->update($validated);

            DB::commit();

            return response()->json([
                'message' => 'image updated.',
            ], 200);

        }catch(\Exception $e){
            DB::rollBack();
            Log::error('image update failed', [
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


      // Verify the user verification code
    public function verifyEmail(Request $request){
        try{

            $request->validate([
                'email' => 'required|email|exists:users,email',
                'code' => 'required|string|size:6',
            ]);

            $user = User::where('email', $request->email)
                        ->where('email_verification_code', strtoupper($request->code))
                        ->first();

            if (!$user) {
                return response()->json(['message' => 'Invalid verification code.'], 400);
            }

            if ($user->email_verification_code_expires_at < now()) {
                return response()->json([
                    'message' => 'Verification code is invalid or expired.'
                ], 400);
            }

            $user->email_verified_at = now();
            $user->email_verification_code = null;
            $user->save();

            return response()->json(['message' => 'Email verified successfully.'], 201);

        }catch (Exception $e) {
            Log::error($e->getMessage(), ['exception' => $e]);

            return response()->json([
                'message' => 'Verification Failed'
            ], 500);
        }
    }



    // Resend verification code email
    public function resendVerification(Request $request){
        try{

            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);

            $user = User::where('email', $request->email)->first();

            if ($user->email_verified_at) {
                return response()->json([
                    'message' => 'Email is already verified.'
                ], 400);
            }

            $newCode = strtoupper(Str::random(6));

            $user->update([
                'email_verification_code' => $newCode,
                'email_verification_code_expires_at' => now()->addHour(),
            ]);

            Mail::to($user->email)->send(new VerifyEmailCodeMail($user));

            return response()->json([
                'message' => 'A new verification code has been sent to your email.'
            ]);

        } catch (Exception $e) {
            Log::error($e->getMessage(), ['exception' => $e]);

            return response()->json([
                'message' => app()->environment('production') ? 'Something Went Wrong..!!!' : $e->getMessage()
            ], 500);
        }
    }



    // Logout for only current device
    public function logoutHandler(Request $request){
        try {

            $user = $request->user();

            // ✅ Update last_logged_in
            $user->update(['last_logged_in' => now()]);

            $token = $request->user()->currentAccessToken();

            if ($token) {
                // ✅ Find related session
                $sessionUuid = $token->user_session_uuid;

                if ($sessionUuid) {
                    $user->sessions()->where('status', 'active')->where('uuid', $sessionUuid)->update([
                        'status' => 'expired',
                        'last_used_at' => now(),
                    ]);
                }

                // ✅ Revoke token
                $token->delete();
            }

            // ✅ Delete all expired sessions for this user
            $deletedSessionsCount = $user->sessions()->where('status', 'expired')->delete();

            Log::info('Expired sessions cleaned up during logout', [
                'user_id' => $user->id,
                'deleted_sessions_count' => $deletedSessionsCount,
            ]);

            return response()->json([
                'message' => 'Logged out successfully'
            ], 200);

        } catch (Exception $e) {
            Log::error($e->getMessage(), ['exception' => $e]);

            return response()->json([
                'message' => 'Logout failed'
            ], 500);
        }
    }

    // Logout from all devices
    public function logoutAllDevicesHandler(Request $request)
    {
        try {
            $user = $request->user();

            // ✅ Update last_logged_in timestamp
            $user->update([
                'last_logged_in' => now(),
            ]);

            // ✅ Get current access token
            $currentToken = $request->user()->currentAccessToken();

            if ($currentToken) {
                // Expire *all* active sessions
                $user->sessions()
                    ->where('status', 'active')
                    ->update([
                        'status' => 'expired',
                        'last_used_at' => now(),
                    ]);

                // Delete ALL Sanctum tokens (log out everywhere)
                $user->tokens()->delete();
            }

            // ✅ Delete all expired sessions for this user
            $deletedSessionsCount = $user->sessions()->where('status', 'expired')->delete();

            Log::info('Expired sessions cleaned up during logout', [
                'user_id' => $user->id,
                'deleted_sessions_count' => $deletedSessionsCount,
            ]);

            return response()->json([
                'message' => 'Logged out from all devices successfully'
            ], 200);

        } catch (Exception $e) {
            Log::error($e->getMessage(), ['exception' => $e]);

            return response()->json([
                'message' => 'Logout failed'
            ], 500);
        }
    }



    //SendPassword reset link
    public function sendResetLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        ],[
            'email.required' => 'A valid email is required to proceed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->first(),
            ], 422);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Password reset link sent.'], 200)
            : response()->json(['message' => 'Unable to send reset link.'], 400);
    }


    //Reset Password
    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ],[
            'password.confirmed' => 'Password does not match',
            'password.min' => 'Password is too short'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->first(),
            ], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password has been reset successfully.'], 200)
            : response()->json(['message' => __($status)], 400);
    }



    protected function deleteCloudinaryImage($publicId)
    {
        try {

            $cloudinary = new \Cloudinary\Cloudinary([
                'cloud' => [
                    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                    'api_key'    => env('CLOUDINARY_API_KEY'),
                    'api_secret' => env('CLOUDINARY_API_SECRET'),
                ],
            ]);
            $cloudinary->uploadApi()->destroy($publicId);
        } catch (\Exception $e) {
            Log::warning('Failed to delete Cloudinary image', [
                'public_id' => $publicId,
                'error' => $e->getMessage(),
            ]);
        }
    }


    //Update logged in user password
    public function updatePassword(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'currentPassword' => 'required|string',
                'newPassword' => [
                    'required',
                    'string',
                    'min:8',
                    'different:currentPassword',
                    'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
                ],
                'confirmPassword' => 'required|same:newPassword',
            ], [
                'newPassword.regex' => 'Password should contain one uppercase letter, one number, and one special character.',
                'confirmPassword.same' => 'The password confirmation does not match.'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Check current password
            if (!Hash::check($request->currentPassword, $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect.'
                ], 403);
            }

            // Prevent reusing the same password
            if (Hash::check($request->newPassword, $user->password)) {
                return response()->json([
                    'message' => 'New password must be different from the current password.'
                ], 422);
            }

            DB::beginTransaction();

            // Update password securely
            $user->update([
                'password' => Hash::make($request->newPassword),
            ]);

            DB::commit();

            // Optionally, log the password change event
            Log::info('User password updated successfully', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'ip'      => request()->ip(),
            ]);

            // Optionally: Invalidate other sessions/tokens
            // $user->tokens()->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Password updated successfully.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Password update failed', [
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

    /**
     * Get device/browser/platform information with support for forwarded headers
     */
    private function getDeviceInfo(Request $request)
    {
        // Check for forwarded User-Agent from frontend (Next.js)
        $userAgent = $request->header('X-Real-User-Agent') ?: $request->header('User-Agent', 'Unknown');
        Agent::setUserAgent($userAgent);

        $platform = Agent::platform();
        $browser = Agent::browser();
        $device = Agent::device() ?: (Agent::isMobile() ? 'Mobile' : (Agent::isTablet() ? 'Tablet' : (Agent::isDesktop() ? 'Desktop' : 'Unknown')));

        // Fallback detection for cases where Agent returns false/empty
        if (!$platform || !$browser) {
            $fallbackData = $this->parseUserAgentFallback($userAgent);
            $platform = $platform ?: $fallbackData['platform'];
            $browser = $browser ?: $fallbackData['browser'];
        }

        return [
            'user_agent' => $userAgent,
            'platform' => $platform,
            'browser' => $browser,
            'device' => $device,
            'is_mobile' => Agent::isMobile(),
            'is_tablet' => Agent::isTablet(),
            'is_desktop' => Agent::isDesktop(),
        ];
    }

    /**
     * Fallback User-Agent parsing for cases where Agent library fails
     */
    private function parseUserAgentFallback($userAgent)
    {
        $platform = 'Unknown';
        $browser = 'Unknown';

        // If user agent is minimal (like "node"), try to infer from context
        if (strtolower($userAgent) === 'node' || strpos(strtolower($userAgent), 'node') !== false) {
            $platform = 'Server/CLI';
            $browser = 'Node.js';
        }
        // Check for common platforms
        elseif (preg_match('/Windows NT/i', $userAgent)) {
            $platform = 'Windows';
        }
        elseif (preg_match('/Mac OS X|macOS/i', $userAgent)) {
            $platform = 'macOS';
        }
        elseif (preg_match('/Linux/i', $userAgent)) {
            $platform = 'Linux';
        }
        elseif (preg_match('/Android/i', $userAgent)) {
            $platform = 'Android';
        }
        elseif (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
            $platform = 'iOS';
        }

        // Check for common browsers (only if not already detected as Node.js)
        if ($browser === 'Unknown') {
            if (preg_match('/Chrome/i', $userAgent)) {
                $browser = 'Chrome';
            }
            elseif (preg_match('/Firefox/i', $userAgent)) {
                $browser = 'Firefox';
            }
            elseif (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
                $browser = 'Safari';
            }
            elseif (preg_match('/Edge/i', $userAgent)) {
                $browser = 'Edge';
            }
            elseif (preg_match('/Opera/i', $userAgent)) {
                $browser = 'Opera';
            }
            elseif (preg_match('/Postman/i', $userAgent)) {
                $browser = 'Postman';
                $platform = 'API Client';
            }
            elseif (preg_match('/curl/i', $userAgent)) {
                $browser = 'cURL';
                $platform = 'Command Line';
            }
            elseif (preg_match('/wget/i', $userAgent)) {
                $browser = 'wget';
                $platform = 'Command Line';
            }
        }

        return [
            'platform' => $platform,
            'browser' => $browser
        ];
    }


}
