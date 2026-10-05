<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    protected $fillable = ['name', 'username', 'email', 'photo', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['password' => 'hashed'];

    protected function username(): Attribute
    {
        return Attribute::make(set: fn (string $value) => strtolower(trim($value)));
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }

        return asset('storage/'.$this->photo);
    }
}
