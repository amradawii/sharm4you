<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale') ?? config('locales.default', 'en');

        if (! array_key_exists($locale, config('locales.supported', []))) {
            $locale = config('locales.default', 'en');
        }

        App::setLocale($locale);
        View::share('currentLocale', $locale);

        return $next($request);
    }
}
