<?php

namespace App\Http\Requests\Admin;

use App\Models\Package;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePackageTierRequest extends FormRequest
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
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('remove_image')) {
            $this->merge([
                'remove_image' => filter_var($this->input('remove_image'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function withValidator($validator): void
    {
        $currentHours = (float) $this->route('hours');
        $newHours = (float) $this->input('hours');

        $validator->after(function ($validator) use ($currentHours, $newHours) {
            if ($newHours !== $currentHours && Package::where('hours', $newHours)->exists()) {
                $validator->errors()->add('hours', 'A package with this duration already exists.');
            }

            $times = $this->input('available_times', []);
            if (is_array($times) && count($times) !== count(array_unique($times))) {
                $validator->errors()->add('available_times', 'Time slots must be unique.');
            }
        });
    }
}
