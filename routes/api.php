<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;


Route::get('/clear', function() {
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    return "Cleared!";
});

Route::controller(\App\Http\Controllers\Auth\AuthController::class)->group(function(){
    Route::post('/register', 'register')->name('register')->middleware('throttle:resend-request');
    Route::post('/login', 'login')->name('login')->middleware('throttle:resend-request');
    Route::post('/verify-email', 'verifyEmail')->middleware('throttle:resend-request');
    Route::post('/resend-verification', 'resendVerification')->middleware('throttle:resend-request');

});


Route::middleware(['auth:sanctum', 'role:admin', 'ability:server:admin'])->group(function (){

    // Other admin routes
    Route::controller(\App\Http\Controllers\Admin\DashboardController::class)->group(function (){
        Route::get('/admin/dashboard', 'index');

    });

});

Route::middleware(['auth:sanctum', 'role:client', 'ability:server:client'])->group(function (){

    // Other admin routes
    Route::controller(\App\Http\Controllers\Client\DashboardController::class)->group(function (){
        Route::get('/client/dashboard', 'index');
        Route::get('/client', 'getClientDetails');

    });

     Route::controller(\App\Http\Controllers\Client\ClientController::class)->group(function (){
        Route::get('/client', 'getClientDetails');

    });



});


Route::middleware(['auth:sanctum', 'role:nurse'])->group(function (){

    // Other admin routes
    Route::controller(\App\Http\Controllers\Nurse\DashboardController::class)->group(function (){
        Route::get('/nurse/dashboard', 'index');

    });

});

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');



//FORGOT PASSWORD FUNCTION
// Route::post('/forgot-password', function (Request $request) {
//     $validator = Validator::make($request->all(), [
//         'email' => 'required|email',
//     ],[
//         'email.required' => 'A valid email is required to proceed'
//     ]);
//     if ($validator->fails()) {
//         return response()->json([
//             'status' => 422,
//             'errors' => $validator->errors(),
//         ]);
//     }
//     $status = Password::sendResetLink($request->only('email'));
//     return $status === Password::RESET_LINK_SENT
//         ? response()->json(['status' => 200, 'message' => __($status)])
//         : response()->json(['status' => 401, 'message' => __($status)]);
// })->middleware('guest')->name('password.email');


// //RESET PASSWORD FUNCTION
// Route::post('/reset-password', function (Request $request) {
//     $validator = Validator::make($request->all(), [
//         'token' => 'required',
//         'email' => 'required|email',
//         'password' => 'required|min:8|confirmed',
//     ],[
//         'password.confirmed' => 'Password does not match',
//         'password.min' => 'Password is too short'
//     ]);
//     if ($validator->fails()) {
//         return response()->json([
//             'status' => 422,
//             'errors' => $validator->errors(),
//         ]);
//     }

//     $status = Password::reset(
//         $request->only('email', 'password', 'password_confirmation', 'token'),
//         function ($user, $password) {
//             $user->forceFill([
//                 'password' => Hash::make($password)
//             ])->setRememberToken(Str::random(60));
//             $user->save();
//             event(new PasswordReset($user));
//         }
//     );

//     return $status === Password::PASSWORD_RESET
//         ? response()->json(['status' => 200, 'message' => __($status)])
//         : response()->json(['status' => 498, 'message' => __($status)]);
// })->middleware('guest')->name('password.update');


