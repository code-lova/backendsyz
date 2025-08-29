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

    //2FA auth
    Route::post('/auth/verify-2fa', 'verify2FA')->middleware('throttle:verify-2fa');
    Route::post('/auth/resend-2fa', 'resend2FA')->middleware('throttle:resend-2fa');

});

Route::middleware(['auth:sanctum'])->group(function (){
    Route::controller(\App\Http\Controllers\Auth\AuthController::class)->group(function (){
        Route::get('/logout', 'logoutHandler');
        Route::get('/logout-all-devices', 'logoutAllDevicesHandler');
        Route::put('/location', 'updateUserLocation');
        Route::put('/image', 'updateImage');
        Route::put('/update-password', 'updatePassword');
    });

    Route::controller(\App\Http\Controllers\Healthworker\UnavailableDateController::class)->group(function(){
        Route::post('/unavailable-dates/toggle', 'toggle');
        Route::get('/unavailable-dates', 'getUnavailableDates');

    });

    Route::controller(\App\Http\Controllers\Auth\SettingsController::class)->group(function (){
        Route::post('/enable-2fa', 'enable2FA');
        Route::post('/disable-2fa', 'disable2FA');
        Route::get('/user-sessions', 'getUserSessions');
        Route::post('/update-settings', 'updateSettings');
        Route::get('/user-settings', 'getUserSettings');
    });

    Route::controller(\App\Http\Controllers\Auth\UserController::class)->group(function (){
        Route::delete('/delete-account', 'deleteUserAccount');
    });

});


Route::middleware(['auth:sanctum', 'role:admin', 'ability:server:admin'])->group(function (){
    // Other admin routes
    Route::controller(\App\Http\Controllers\Admin\DashboardController::class)->group(function (){
        Route::get('/admin/dashboard', 'index');

    });

    Route::controller(\App\Http\Controllers\Admin\AdminController::class)->group(function (){
        Route::get('/admin', 'getAdminDetails');

    });

    Route::controller(\App\Http\Controllers\Admin\UserManagementController::class)->group(function (){
        Route::get('/allusers', 'listUsers');
        Route::put('/user/{id}/update-user', 'updateUser');
        Route::delete('/user/{id}', 'deleteUser');

        //Fetch all users that have roles health workers
        Route::get('/all-health-workers', 'getHealthWorkers');
    });

    Route::controller(\App\Http\Controllers\Admin\BookingRequests::class)->group(function (){
        Route::get('/booking-requests', 'listBookingRequests');
        Route::put('/booking-request/{id}/processing', 'processingBookingRequest');
        Route::put('/booking-request/{id}/done', 'doneBookingRequest');
        Route::put('/booking-request/{id}/cancel', 'cancelBookingRequest');
        Route::delete('/booking-request/{id}/delete', 'destroyBookingRequest');

    });

});



Route::middleware(['auth:sanctum', 'role:client', 'ability:server:client'])->group(function (){

    Route::controller(\App\Http\Controllers\Client\DashboardController::class)->group(function (){
        Route::get('/activity-stats', 'monthlyBookingActivity');
        Route::get('/total-appointment', 'getTotalAppointment');
        Route::get('/next-appointment', 'getNextAppointment');
        Route::get('/verified-health-workers', 'getVerifiedHealthWorkers');
    });

     Route::controller(\App\Http\Controllers\Client\ClientController::class)->group(function (){
        Route::get('/client', 'getClientDetails');
        Route::put('/profile-update', 'updateClientProfile');
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



