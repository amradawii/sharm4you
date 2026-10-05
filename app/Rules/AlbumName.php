<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AlbumName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match("/\A[\p{L}\p{M}\p{N}\p{Zs}.,'’‘&()–-]+\z/u", $value)
            || ! preg_match('/[\p{L}\p{N}]/u', $value)) {
            $fail('Album names must contain letters or numbers and may use spaces and normal punctuation, not slashes or special symbols.');
        }
    }
}
