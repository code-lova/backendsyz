<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmailCodeMail;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // Register API
    public function register(Request $request){
        try{

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:200|unique:users,name',
                'phone' => 'required|regex:/^\+?[0-9]{10,15}$/|unique:users,phone',
                'email' => 'required|email|max:200|unique:users,email',
                'password' => 'required|min:8|confirmed',
                'role' => 'required|in:admin,client,nurse',
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
                'password' => Hash::make($request->password),
                'email_verification_code' => $verificationCode,
                'email_verification_code_expires_at' => now()->addMinutes(10),
            ]);

            $token = $user->createToken('API Token')->plainTextToken;

            // Send email
            if($user->email){
                Mail::to($user->email)->send(new VerifyEmailCodeMail($user));
            }

            return response()->json([
                'message' => 'User registered. Please verify your email.',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token
            ], 201);

        }catch(Exception $e){
            // Log the exception message along with the stack trace for better debugging
            Log::error($e->getMessage(), ['exception' => $e]);

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

            // if (is_null($user->email_verified_at)) {
            //     return response()->json([
            //         'message' => 'Please verify your email before logging in.'
            //     ], 403);
            // }

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
            }elseif($user->role === 'nurse') {
                $abilities = ['server:nurse'];
                $tokenName = $user->email.'_NurseToken';
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
                    'id' => $user->id,
                    'fullname' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'accessToken' => $accessToken
            ]);

        } catch (Exception $e) {
            Log::error($e->getMessage(), ['exception' => $e]);

            return response()->json([
                'message' => app()->environment('production') ? 'Login failed' : $e->getMessage()
            ], 500);
        }
    }


    // Logout APi
    public function logout(Request $request){
        try {
            $request->user()->currentAccessToken()->delete();

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






}
