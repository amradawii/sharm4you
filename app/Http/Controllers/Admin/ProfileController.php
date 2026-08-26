<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePasswordRequest;
use App\Http\Requests\Admin\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show()
    {
        return view('admin.profile', [
            'admin' => Auth::guard('admin')->user(),
        ]);
    }

    public function updateInfo(UpdateProfileRequest $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        $admin->fill($request->safe()->only(['name', 'username', 'email']));

        if ($request->hasFile('photo')) {
            if ($admin->photo) {
                Storage::disk('public')->delete($admin->photo);
            }

            $admin->photo = $request->file('photo')->store('admins/'.$admin->id, 'public');
        }

        $admin->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'photo_url' => $admin->photo_url,
            'initial' => strtoupper(substr($admin->name, 0, 1)),
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        if (! Hash::check($request->input('current_password'), $admin->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $admin->update([
            'password' => $request->input('password'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }
}
