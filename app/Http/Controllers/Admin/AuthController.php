<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\Admin;

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
        try {
            // Validate credentials
            $credentials = $request->validate([
                'username' => ['required', 'string', 'min:3', 'regex:/^[a-zA-Z0-9_]+$/'],
                'password' => ['required', 'string', 'min:6'],
            ], [
                'username.required' => 'اسم المستخدم مطلوب',
                'username.min' => 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل',
                'username.regex' => 'اسم المستخدم يمكن أن يحتوي فقط على حروف وأرقام وشرطة سفلية',
                'password.required' => 'كلمة المرور مطلوبة',
                'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
            ]);
        } catch (ValidationException $e) {
            // Return JSON validation errors for AJAX requests
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من البيانات',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        // Attempt to authenticate
        if (Auth::guard('admin')->attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password']
        ])) {
            // Regenerate session to prevent fixation attacks
            $request->session()->regenerate();

            $adminName = Auth::guard('admin')->user()->name;

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'مرحباً بعودتك، ' . $adminName,
                    'redirect' => route('admin.dashboard')
                ], 200);
            }

            // Traditional redirect for non-AJAX requests
            return redirect()->intended(route('admin.dashboard'))
                ->with('success', 'مرحباً بعودتك، ' . $adminName);
        }

        // Authentication failed
        $errorMessage = 'بيانات الاعتماد المقدمة غير صحيحة.';

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'errors' => [
                    'username' => [$errorMessage]
                ]
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
