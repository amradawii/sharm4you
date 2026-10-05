<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\PrivatePhotoHeaders;
use App\Http\Middleware\SetAdminLocale;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'locale' => SetLocale::class,
            'auth.admin' => AdminMiddleware::class,
            'admin.locale' => SetAdminLocale::class,
            'private.photos' => PrivatePhotoHeaders::class,
        ]);

        $middleware->redirectUsersTo(function (Request $request) {
            if (Auth::guard('admin')->check()) {
                return route('admin.dashboard');
            }

            return Route::has('home') ? route('home') : '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
        $exceptions->dontFlash(['delivery_code']);
        $exceptions->respond(function ($response, $exception, Request $request) {
            return $request->is('my-photos', 'my-photos/*', '*/my-photos', '*/my-photos/*')
                ? PrivatePhotoHeaders::protect($response) : $response;
        });
    })->create();
