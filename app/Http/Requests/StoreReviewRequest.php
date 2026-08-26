<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $countries = array_keys(config('countries.list', []));

        return [
            'name' => ['required', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2', Rule::in($countries)],
            'comment' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $code = strtoupper((string) $this->input('country_code'));
        $country = config("countries.list.{$code}");

        if ($country) {
            $this->merge([
                'country_code' => $code,
                'country' => $country,
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'country_code.in' => 'Please select a valid country.',
            'comment.min' => 'Please write a bit more (at least 10 characters).',
        ];
    }
}
