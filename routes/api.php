<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/clear', function() {
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    return "Cleared!";
});


// Route::get('/reset-password/{token}', function ($token) {
//     return response()->json([
//         'message' => 'Redirect to frontend handled separately.',
//         'token' => $token,
//     ]);
// })->name('password.reset');


Route::controller(\App\Http\Controllers\Auth\AuthController::class)->group(function(){
    Route::post('/register', 'register')->name('register');
    Route::post('/login', 'login')->name('login');
    Route::post('/verify-email', 'verifyEmail');
    Route::post('/resend-verification', 'resendVerification')->middleware('throttle:resend-request');

    //Forgot and RestePassword
    Route::post('/forgot-password', 'sendResetLink');
    Route::post('/reset-password', 'reset');

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

    });

     Route::controller(\App\Http\Controllers\Client\ClientController::class)->group(function (){
        Route::get('/client', 'getClientDetails');
        Route::put('/client/update', 'update');


    });



});


Route::middleware(['auth:sanctum', 'role:nurse'])->group(function (){

    // Other admin routes
    Route::controller(\App\Http\Controllers\Nurse\DashboardController::class)->group(function (){
        Route::get('/nurse/dashboard', 'index');

    });

});



