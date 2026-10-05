<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Album extends Model
{
    protected $fillable = [
        'name_en',
        'name_ru',
        'name_it',
        'name_ro',
        'name_bg',
        'name_tr',
        'slug',
        'is_active',
        'sort_order',
        'cover_photo_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /* ── Relationships ── */

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class)->orderBy('sort_order');
    }

    public function coverPhoto(): BelongsTo
    {
        return $this->belongsTo(Photo::class, 'cover_photo_id');
    }

    /* ── Accessors ── */

    /**
     * Return localized name based on current app locale.
     */
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        $col = "name_{$locale}";

        return $this->{$col} ?? $this->name_en;
    }

    /**
     * URL-ready cover image (first photo fallback).
     */
    public function getCoverUrlAttribute(): ?string
    {
        if ($this->cover_photo_id && $this->coverPhoto) {
            return $this->coverPhoto->url;
        }

        $first = $this->photos()->first();

        return $first?->url;
    }

    /* ── Scopes ── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }

    /* ── Boot ── */

    protected static function booted(): void
    {
        static::creating(function (Album $album) {
            if (empty($album->slug)) {
                $album->slug = static::generateUniqueSlug($album->name_en);
            }
            $album->storagePath();
        });

        static::updating(function (Album $album) {
            $album->storagePath();
        });
        static::deleting(function (Album $album) {
            $album->storagePath();
        });
    }

    /**
     * Generate a unique slug from the English name.
     */
    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        if (! preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $base)) {
            $base = 'album';
        }
        $base = rtrim(substr($base, 0, 240), '-');
        $slug = $base;
        $counter = 1;

        while (
            static::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Storage directory path for this album's photos.
     */
    public function storagePath(): string
    {
        if (! is_string($this->slug) || strlen($this->slug) > 255
            || ! preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $this->slug)) {
            throw ValidationException::withMessages([
                'album' => 'This album has an unsafe storage path. No files were changed; contact the developer to repair it.',
            ]);
        }

        return "albums/{$this->slug}";
    }
}
