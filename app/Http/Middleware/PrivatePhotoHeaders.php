<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrivatePhotoHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        return self::protect($next($request));
    }

    public static function protect(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
