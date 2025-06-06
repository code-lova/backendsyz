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
            return Limit::perMinutes(20, 2)->by($request->ip()); // Limit to 2 requests per 20 minutes
        });

        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            $frontendUrl = config('app.frontend_url');
            return $frontendUrl . '/password/reset?code=' . $token .
           '&exp=' . now()->addMinutes(10)->timestamp .
           '&email=' . urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
