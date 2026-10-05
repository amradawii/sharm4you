<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the login form
     */
    public function showLogin()
    {
        // Redirect if already logged in
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        if (is_string($request->input('username'))) {
            $request->merge(['username' => strtolower(trim($request->input('username')))]);
        }

        try {
            // Validate credentials
            $credentials = $request->validate([
                'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9_]+$/'],
                'password' => ['required', 'string', 'min:6', 'max:255'],
            ], [
                'username.required' => 'Username is required.',
                'username.min' => 'Username must be at least 3 characters.',
                'username.regex' => 'Username may only contain letters, numbers, and underscores.',
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 6 characters.',
            ]);
        } catch (ValidationException $e) {
            // Return JSON validation errors for AJAX requests
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please check your login details.',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        }

        // Attempt to authenticate
        if (Auth::guard('admin')->attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ])) {
            // Regenerate session to prevent fixation attacks
            $request->session()->regenerate();

            $adminName = Auth::guard('admin')->user()->name;

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Welcome back, '.$adminName.'.',
                    'redirect' => route('admin.dashboard'),
                ], 200);
            }

            // Traditional redirect for non-AJAX requests
            return redirect()->intended(route('admin.dashboard'))
                ->with('success', 'Welcome back, '.$adminName.'.');
        }

        // Authentication failed
        $errorMessage = 'The provided credentials are incorrect.';

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'errors' => [
                    'username' => [$errorMessage],
                ],
            ], 401);
        }

        // Traditional redirect for non-AJAX requests
        return back()->withErrors([
            'username' => $errorMessage,
        ])->onlyInput('username');
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('success', 'You have been logged out successfully');
    }
}
