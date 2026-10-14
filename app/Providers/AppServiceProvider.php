<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        RateLimiter::for('admin-login', function (Request $request) {
            $username = $request->input('username');
            $username = is_string($username) ? strtolower(trim($username)) : '';
            $response = fn (Request $request, array $headers) => response()->json([
                'success' => false,
                'message' => 'Too many login attempts. Please wait before trying again.',
            ], 429, $headers);

            return [
                Limit::perMinute(5)->by('username:'.hash('sha256', $username).'|'.$request->ip())->response($response),
                Limit::perMinute(20)->by('ip:'.$request->ip())->response($response),
            ];
        });

        foreach (['bookings' => 3, 'reviews' => 3, 'photo-lookup' => 5] as $name => $attempts) {
            RateLimiter::for($name, fn (Request $request) => Limit::perMinute($attempts)->by($request->ip()));
        }

        // Packages + slots are now passed only by the routes that actually show
        // the booking modal (home, services). The layout falls back to an empty
        // collection via `@php($packages = $packages ?? collect())` so pages that
        // don't need packages (/my-photos, /about, /gallery, album pages) skip
        // this query entirely instead of running it on every request.
    }
}
