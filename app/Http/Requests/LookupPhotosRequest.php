<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LookupPhotosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Normalise so tourists can type in any case / with spaces.
        foreach (['booking_number', 'delivery_code'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => strtoupper(trim($this->input($field)))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'booking_number' => ['required', 'string', 'max:40'],
            'delivery_code' => ['required', 'string', 'max:40'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_number.required' => __('site.my_photos.error_number'),
            'delivery_code.required' => __('site.my_photos.error_code'),
            'delivery_code.string' => __('site.my_photos.error_code'),
            'delivery_code.max' => __('site.my_photos.error_code'),
            'booking_number.string' => __('site.my_photos.error_number'),
            'booking_number.max' => __('site.my_photos.error_number'),
        ];
    }
}
