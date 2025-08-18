<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/clear', function() {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    return "Cleared!";
});


// Route::get('/reset-password/{token}', function ($token) {
//     return response()->json([
//         'message' => 'Redirect to frontend handled separately.',
//         'token' => $token,
//     ]);
// })->name('password.reset');

Route::get('/', function () {
    return response()->json([
        'message' => 'This is a private gateway API',
        'version' => '1.1.0'
    ]);
});


Route::controller(\App\Http\Controllers\Auth\AuthController::class)->group(function(){
    Route::post('/register', 'register')->name('register')->middleware('throttle:register');
    Route::post('/login', 'login')->name('login')->middleware('throttle:login');
    Route::post('/verify-email', 'verifyEmail');
    Route::post('/resend-verification', 'resendVerification')->middleware('throttle:resend-request');

    //Forgot and RestePassword
    Route::post('/forgot-password', 'sendResetLink')->middleware('throttle:forgot-password');
    Route::post('/reset-password', 'reset');

});

Route::middleware(['auth:sanctum'])->group(function (){
    Route::controller(\App\Http\Controllers\Auth\AuthController::class)->group(function (){
        Route::get('/logout', 'logoutHandler');
        Route::put('/location', 'updateUserLocation');
        Route::put('/image', 'updateImage');
    });

    Route::controller(\App\Http\Controllers\Healthworker\UnavailableDateController::class)->group(function(){
        Route::post('/unavailable-dates/toggle', 'toggle');
        Route::get('/unavailable-dates', 'getUnavailableDates');

    });

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

    Route::controller(\App\Http\Controllers\Client\BookingAppointmentController::class)->group(function () {
        Route::post('/booking-appointment', 'create');
        Route::get('/booking-appointment', 'show');
        Route::put('/booking-appointment/{id}', 'cancelAppointment');
        Route::put('/mark-done/{id}', 'doneAppointment');
        Route::delete('/booking-appointment/{id}', 'destroy');

    });

    Route::controller(\App\Http\Controllers\Client\SupportTicketController::class)->group(function () {
        Route::post('/ticket-support', 'createTicketMessage');
        Route::get('/ticket-support', 'getTicketAndMessages');

        Route::post('/ticket-support/{id}/reply', 'replyToTicket');

    });
});



Route::middleware(['auth:sanctum', 'role:healthworker', 'ability:server:healthworker'])->group(function (){

    Route::controller(\App\Http\Controllers\Healthworker\ProfileController::class)->group(function (){
        Route::put('/healthworker/update', 'update');
        Route::get('/healthworker', 'authHealthDetails');
    });

    Route::controller(\App\Http\Controllers\Auth\GuidedRateSystemController::class)->group(function (){
        Route::get('/guided-rate-system', 'show');
        Route::put('/guided-rate-system', 'update');
    });

    Route::controller(\App\Http\Controllers\Healthworker\SupportController::class)->group(function () {
        Route::post('/support', 'createSupportMessage');
        Route::get('/support', 'getSupportMessages');

    });



});



