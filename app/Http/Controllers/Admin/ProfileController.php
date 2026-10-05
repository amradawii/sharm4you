<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePasswordRequest;
use App\Http\Requests\Admin\UpdateProfileRequest;
use App\Support\PhotoStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\ImageManager;
use Throwable;

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

        $oldPhoto = $admin->photo;
        $newPhoto = null;
        try {
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                try {
                    (new ImageManager(new GdDriver))->read($file->getRealPath());
                } catch (DecoderException $e) {
                    throw ValidationException::withMessages(['photo' => 'This image cannot be decoded. Please re-save it and try again.']);
                }
                $newPhoto = $file->hashName('admins/'.$admin->id);
                PhotoStorage::put('public', $newPhoto, $file->getContent());
                $admin->photo = $newPhoto;
            }
            $admin->save();
        } catch (Throwable $e) {
            PhotoStorage::cleanup('public', $newPhoto);
            throw $e;
        }
        $warning = $newPhoto && $oldPhoto && ! PhotoStorage::cleanup('public', $oldPhoto)
            ? 'Your profile was saved, but the old photo could not be removed. Contact the developer.' : null;

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'photo_url' => $admin->photo_url,
            'warning' => $warning,
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
