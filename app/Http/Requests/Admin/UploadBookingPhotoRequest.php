<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UploadBookingPhotoRequest extends FormRequest
{
    /** Max upload size per photo, in kilobytes (10 MB). */
    public const MAX_KB = 10240;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // One file per request — the admin UI uploads sequentially so shared
        // hosting never has to handle a large multi-file POST.
        return [
            'photo' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:'.self::MAX_KB],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'No image was received.',
            'photo.mimes' => 'Only JPEG, PNG, and WebP images are allowed.',
            'photo.max' => 'Each image must not exceed 10 MB.',
            'photo.file' => 'Invalid file upload.',
        ];
    }
}
