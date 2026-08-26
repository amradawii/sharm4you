<?php

namespace App\Providers;

use App\Models\Package;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
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

        View::composer('layouts.app', function ($view) {
            $packages = Package::active()->forLocale()->ordered()->get();

            $view->with([
                'packages' => $packages,
                'packagesSlots' => Package::slotsMap($packages),
            ]);
        });
    }
}
