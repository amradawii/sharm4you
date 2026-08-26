<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class AdminMiddleware extends Middleware
{
    /**
     * Redirect unauthenticated admins to the login page.
     */
    protected function redirectTo($request): ?string
    {
        if (! $request->expectsJson()) {
            return route('admin.login');
        }

        return null;
    }

    /**
     * Always authenticate using the admin guard by default.
     */
    protected function authenticate($request, array $guards)
    {
        $guards = empty($guards) ? ['admin'] : $guards;

        parent::authenticate($request, $guards);
    }
}
