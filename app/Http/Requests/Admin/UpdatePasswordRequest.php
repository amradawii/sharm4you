<?php

namespace App\Http\Requests\Admin;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'max:255'],
            'password' => [
                'bail',
                'required',
                'string',
                'max:255',
                'confirmed',
                function (string $attribute, mixed $value, Closure $fail) {
                    $maxBytes = config('hashing.driver') === 'bcrypt' ? 72 : 255;
                    if (strlen($value) > $maxBytes) {
                        $fail('New passwords must not exceed '.$maxBytes.' UTF-8 bytes. Some characters use more than one byte.');
                    }
                },
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password' => 'Password must be at least 8 characters and include uppercase, lowercase, a digit, and a special character.',
        ];
    }
}
