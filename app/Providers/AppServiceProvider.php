<?php

namespace App\Providers;

use App\Services\PerformanceMonitoringService;
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
        // Enable performance monitoring in development
        if (config('app.debug')) {
            PerformanceMonitoringService::enableQueryLogging();
        }

        RateLimiter::for('resend-request', function (Request $request) {
            return Limit::perMinutes(10, 2)->by($request->ip()); // Limit to 2 requests per 20 minutes
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip()); // 5 attempts per minute
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(8)->by($request->ip());  //8 requests/minute
        });

        RateLimiter::for('forgot-password', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());  //5 requests/minute
        });

        RateLimiter::for('verify-2fa', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());  //10 requests/minute
        });

        RateLimiter::for('resend-2fa', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());  //3 requests/minute
        });

        //Frontnend contact us form rate limiter
        RateLimiter::for('contact-us', function (Request $request) {
            return Limit::perMinutes(10, 3)->by($request->ip()); // Limit to 3 requests per 10 minutes
        });

        //Frontend  subscribers rate limiter
        RateLimiter::for('subscribe-newsletter', function (Request $request) {
            return Limit::perMinutes(10, 3)->by($request->ip()); // Limit to 3 requests per 10 minutes
        });

        RateLimiter::for('support', function (Request $request) {
            return [
                // Allow 5 requests per minute per user (to prevent spam/abuse)
                Limit::perMinute(5)->by($request->user()?->uuid ?: $request->ip()),
                // Allow 10 requests per hour per user (additional protection)
                Limit::perHour(10)->by($request->user()?->uuid ?: $request->ip()),
            ];
        });

        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            $frontendUrl = config('app.frontend_url');
            return $frontendUrl . '/password/reset?code=' . $token .
           '&exp=' . now()->addMinutes(10)->timestamp .
           '&email=' . urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
