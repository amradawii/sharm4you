<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    public const FEATURED_LIMIT = 12;

    protected $fillable = [
        'name',
        'country',
        'country_code',
        'comment',
        'status',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    /* ── Accessors ── */

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-amber-50 text-amber-600',
            self::STATUS_APPROVED => 'bg-emerald-50 text-emerald-600',
            self::STATUS_REJECTED => 'bg-red-50 text-red-500',
            default => 'bg-sharm-sand text-sharm-mist',
        };
    }

    public function getFlagAttribute(): string
    {
        $code = strtoupper((string) $this->country_code);
        if (strlen($code) !== 2) {
            return '';
        }

        return mb_chr(0x1F1E6 + (ord($code[0]) - ord('A')), 'UTF-8')
            .mb_chr(0x1F1E6 + (ord($code[1]) - ord('A')), 'UTF-8');
    }

    /* ── Scopes ── */

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderByDesc('id');
    }
}
