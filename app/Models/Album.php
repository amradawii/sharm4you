<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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
        });
    }

    /**
     * Generate a unique slug from the English name.
     */
    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
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
        return "albums/{$this->slug}";
    }
}
