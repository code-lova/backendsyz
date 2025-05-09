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
    Route::post('/register', 'register')->name('register');
    Route::post('/login', 'login')->name('login');
    Route::post('/verify-email', 'verifyEmail');
    Route::post('/resend-verification', 'resendVerification');

});


Route::middleware(['auth:sanctum', 'role:admin'])->group(function (){

    // Other admin routes
    Route::controller(\App\Http\Controllers\Admin\DashboardController::class)->group(function (){
        Route::get('/admin/dashboard', 'index');

    });

});

Route::middleware(['auth:sanctum', 'role:client'])->group(function (){

    // Other admin routes
    Route::controller(\App\Http\Controllers\Client\DashboardController::class)->group(function (){
        Route::get('/client/dashboard', 'index');

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
