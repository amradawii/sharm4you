<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    public const CURRENCIES = ['USD', 'EUR', 'RUB'];

    protected $fillable = [
        'name_en',
        'name_ru',
        'name_it',
        'name_de',
        'name_uk',
        'description_en',
        'description_ru',
        'description_it',
        'description_de',
        'description_uk',
        'image',
        'price',
        'currency',
        'hours',
        'available_times',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'price' => 'decimal:2',
        'hours' => 'decimal:1',
        'available_times' => 'array',
        'sort_order' => 'integer',
    ];

    /* ── Relationships ── */

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /* ── Accessors ── */

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        $col = "name_{$locale}";

        return $this->{$col} ?? $this->name_en;
    }

    public function getDescriptionAttribute(): string
    {
        $locale = app()->getLocale();
        $col = "description_{$locale}";

        return $this->{$col} ?? $this->description_en;
    }

    public function getFormattedPriceAttribute(): string
    {
        $cfg = config("currencies.symbols.{$this->currency}");
        $symbol = $cfg['symbol'] ?? $this->currency;

        return number_format((float) $this->price, 0).' '.$symbol;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/packages/'.$this->image) : null;
    }

    public function getCurrencyLabelAttribute(): string
    {
        return config("currencies.symbols.{$this->currency}.label", $this->currency);
    }

    /* ── Scopes ── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('hours');
    }

    public function scopeForLocale($query)
    {
        $currency = config('currencies.locale_map.'.app()->getLocale(), 'USD');

        return $query->where('currency', $currency);
    }

    /**
     * Build a {package_id => [available_times]} map for a given collection.
     */
    public static function slotsMap($packages): array
    {
        return $packages->mapWithKeys(fn (self $package) => [
            (string) $package->id => $package->available_times ?? [],
        ])->toArray();
    }
}
