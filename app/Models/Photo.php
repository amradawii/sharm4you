<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Photo extends Model
{
    protected $fillable = [
        'album_id',
        'filename',
        'original_name',
        'alt_en',
        'alt_ru',
        'alt_it',
        'alt_de',
        'alt_uk',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    /* ── Relationships ── */

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    /* ── Accessors ── */

    /**
     * Return localized alt text, falling back to English then original filename.
     */
    public function getAltAttribute(): string
    {
        $locale = app()->getLocale();
        $col = "alt_{$locale}";

        return $this->{$col}
            ?? $this->alt_en
            ?? pathinfo($this->original_name, PATHINFO_FILENAME);
    }

    /**
     * Full public URL for this photo.
     */
    public function getUrlAttribute(): string
    {
        return asset("storage/albums/{$this->album->slug}/{$this->filename}");
    }

    /* ── Scopes ── */

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }

    /* ── Helpers ── */

    /**
     * Count of globally featured photos.
     */
    public static function featuredCount(): int
    {
        return static::where('is_featured', true)->count();
    }
}
