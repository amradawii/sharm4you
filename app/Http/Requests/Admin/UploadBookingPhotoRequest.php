<?php

namespace App\Http\Requests\Admin;

use App\Rules\SafeImage;
use Illuminate\Foundation\Http\FormRequest;

class UploadBookingPhotoRequest extends FormRequest
{
    /** Max upload size per photo, in kilobytes (13 MB). */
    public const MAX_KB = 13312;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // One file per request — the admin UI uploads sequentially so shared
        // hosting never has to handle a large multi-file POST.
        return [
            'photo' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:'.config('photo_delivery.max_upload_kb', self::MAX_KB), new SafeImage],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->route('booking')->photos()->count() >= config('photo_delivery.max_per_booking')) {
                $validator->errors()->add('photo', 'This booking already has the maximum of '.config('photo_delivery.max_per_booking').' photos.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'No image was received.',
            'photo.mimes' => 'Only JPEG, PNG, and WebP images are allowed.',
            'photo.max' => 'Each image must not exceed 13 MB.',
            'photo.file' => 'Invalid file upload.',
        ];
    }
}
