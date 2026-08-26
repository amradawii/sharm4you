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
        $this->merge([
            'booking_number' => strtoupper(trim((string) $this->input('booking_number'))),
            'delivery_code' => strtoupper(trim((string) $this->input('delivery_code'))),
        ]);
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
        ];
    }
}
