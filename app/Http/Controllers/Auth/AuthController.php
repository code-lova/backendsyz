<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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

class AuthController extends Controller
{


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
                    'message' => 'Invalid email or password'
                ], 401);
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

            $accessToken = $user->createToken(
                $tokenName,
                $abilities,
                now()->addDay() // 24-hour expiration
            )->plainTextToken;


            // Return JSON response without setting any cookies
            return response()->json([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->uuid,
                    'fullname' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'accessToken' => $accessToken
            ]);

        } catch (\Throwable $e) {
            Log::error('Login Error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json([
                'message' => app()->environment('production') ? 'Login failed' : $e->getMessage()
            ], 500);
        }
    }


    // Logout APi
    public function logoutHandler(Request $request){
        try {

            $user = $request->user();

            // ✅ Update last_logged_in before logging out
            $updated = $user->update([
                'last_logged_in' => now(),
            ]);

            if ($updated) {
                // ✅ Then revoke the token
                $request->user()->currentAccessToken()->delete();

                return response()->json([
                    'message' => 'Logged out successfully'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Failed to update last login'
                ], 500);
            }
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




}
