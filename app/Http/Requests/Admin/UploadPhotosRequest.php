<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UploadPhotosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photos'   => ['required', 'array', 'min:1', 'max:5'],
            'photos.*' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,avif', 'max:3072'], // 3 MB
        ];
    }

    public function messages(): array
    {
        return [
            'photos.required'  => 'Please select at least one image.',
            'photos.max'       => 'You can upload a maximum of 5 images at a time.',
            'photos.*.mimes'   => 'Only JPEG, PNG, WebP, and AVIF images are allowed.',
            'photos.*.max'     => 'Each image must not exceed 3 MB.',
            'photos.*.file'    => 'Invalid file upload.',
        ];
    }
}
