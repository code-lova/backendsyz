<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('resend-request', function (Request $request) {
            return Limit::perMinutes(10, 2)->by($request->ip()); // Limit to 1 requests per 20 minutes
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip()); // 5 attempts per minute
        });

         RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());  //10 requests/minute
        });

         RateLimiter::for('forgot-password', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());  //5 requests/minute
        });

        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            $frontendUrl = config('app.frontend_url');
            return $frontendUrl . '/password/reset?code=' . $token .
           '&exp=' . now()->addMinutes(10)->timestamp .
           '&email=' . urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
