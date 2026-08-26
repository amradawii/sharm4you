<?php

namespace App\Http\Requests\Admin;

use App\Models\Package;
use Illuminate\Foundation\Http\FormRequest;

class StorePackageTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:255'],
            'name_ru' => ['required', 'string', 'max:255'],
            'name_it' => ['required', 'string', 'max:255'],
            'name_de' => ['required', 'string', 'max:255'],
            'name_uk' => ['required', 'string', 'max:255'],
            'description_en' => ['required', 'string', 'max:1000'],
            'description_ru' => ['required', 'string', 'max:1000'],
            'description_it' => ['required', 'string', 'max:1000'],
            'description_de' => ['required', 'string', 'max:1000'],
            'description_uk' => ['required', 'string', 'max:1000'],
            'price_usd' => ['required', 'numeric', 'min:0', 'max:99999'],
            'price_eur' => ['required', 'numeric', 'min:0', 'max:99999'],
            'price_rub' => ['required', 'numeric', 'min:0', 'max:99999'],
            'hours' => ['required', 'numeric', 'min:0.5', 'max:24'],
            'available_times' => ['required', 'array', 'min:1'],
            'available_times.*' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'image' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,avif', 'max:5120'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (Package::where('hours', $this->input('hours'))->exists()) {
                $validator->errors()->add('hours', 'A package with this duration already exists.');
            }

            $times = $this->input('available_times', []);
            if (is_array($times) && count($times) !== count(array_unique($times))) {
                $validator->errors()->add('available_times', 'Time slots must be unique.');
            }
        });
    }
}
