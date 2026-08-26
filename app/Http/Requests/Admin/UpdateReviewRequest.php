<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReviewRequest extends FormRequest
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
            'is_featured' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
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

        if ($this->has('is_featured')) {
            $this->merge(['is_featured' => filter_var($this->input('is_featured'), FILTER_VALIDATE_BOOLEAN)]);
        }
    }
}
