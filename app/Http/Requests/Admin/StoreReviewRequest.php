<?php

namespace App\Http\Requests\Admin;

use App\Models\Review;
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
            'status' => ['required', Rule::in(Review::STATUSES)],
            'is_featured' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('country_code'))) {
            $this->merge(['country_code' => strtoupper($this->input('country_code'))]);
        }

        if (is_string($this->input('is_featured'))) {
            $value = filter_var($this->input('is_featured'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($value !== null) {
                $this->merge(['is_featured' => $value]);
            }
        }
    }
}
