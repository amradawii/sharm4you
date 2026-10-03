<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAlbumRequest extends FormRequest
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
            'name_ro' => ['required', 'string', 'max:255'],
            'name_bg' => ['required', 'string', 'max:255'],
            'name_tr' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name_en.required' => 'English name is required.',
            'name_ru.required' => 'Russian name is required.',
            'name_it.required' => 'Italian name is required.',
        ];
    }
}
