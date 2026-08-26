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
            return Limit::perMinute(5)
                ->by($request->input('username').'|'.$request->ip())
                ->response(fn () => response()->json([
                    'success' => false,
                    'message' => 'Too many login attempts. Please wait 60 seconds before trying again.',
                ], 429));
        });

        // Packages + slots are now passed only by the routes that actually show
        // the booking modal (home, services). The layout falls back to an empty
        // collection via `@php($packages = $packages ?? collect())` so pages that
        // don't need packages (/my-photos, /about, /gallery, album pages) skip
        // this query entirely instead of running it on every request.
    }
}
