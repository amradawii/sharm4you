<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    public const CURRENCIES = ['USD', 'EUR', 'RUB'];

    public const DISCOUNT_PERCENTAGES = [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70];

    protected $fillable = [
        'name_en',
        'name_ru',
        'name_it',
        'name_ro',
        'name_bg',
        'name_tr',
        'description_en',
        'description_ru',
        'description_it',
        'description_ro',
        'description_bg',
        'description_tr',
        'image',
        'price',
        'discount_percent',
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
        'discount_percent' => 'integer',
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
        return $this->formatPrice((float) $this->price);
    }

    public function getIsDiscountedAttribute(): bool
    {
        return $this->discount_percent > 0;
    }

    public function getDiscountedPriceAttribute(): float
    {
        if (! $this->is_discounted) {
            return round((float) $this->price, 0, PHP_ROUND_HALF_UP);
        }

        return round(
            (float) $this->price * (100 - $this->discount_percent) / 100,
            0,
            PHP_ROUND_HALF_UP
        );
    }

    public function getFormattedDiscountedPriceAttribute(): string
    {
        return $this->formatPrice($this->discounted_price);
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

    private function formatPrice(float $price): string
    {
        $cfg = config("currencies.symbols.{$this->currency}");
        $symbol = $cfg['symbol'] ?? $this->currency;

        return number_format($price, 0).' '.$symbol;
    }
}
