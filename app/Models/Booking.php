<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_CONTACTED,
        self::STATUS_CONFIRMED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    /** Booking submitted by a tourist through the public booking form. */
    public const SOURCE_CUSTOMER = 'customer';

    /** Ad-hoc booking recorded by the admin (not bound by the agency schedule). */
    public const SOURCE_ADMIN = 'admin';

    public const SOURCES = [
        self::SOURCE_CUSTOMER,
        self::SOURCE_ADMIN,
    ];

    /** Prefix for the human-shareable booking number. */
    public const NUMBER_PREFIX = 'SH-';

    /** Number of random characters after the prefix. */
    public const NUMBER_LENGTH = 6;

    /** Number of characters in the delivery access code. */
    public const CODE_LENGTH = 6;

    /**
     * Unambiguous alphabet (no 0/O/1/I/L) for numbers & codes so tourists can
     * read/type them from WhatsApp without confusion.
     */
    public const SAFE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    protected $fillable = [
        'booking_number',
        'package_id',
        'source',
        'session_date',
        'session_time',
        'name',
        'phone',
        'country_code',
        'email',
        'status',
        'currency',
        'amount',
        'notes',
        'delivery_code',
    ];

    protected $casts = [
        'status' => 'string',
        'session_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Auto-assign a unique booking number on create.
        static::creating(function (Booking $booking) {
            if (empty($booking->booking_number)) {
                $booking->booking_number = self::generateBookingNumber();
            }
        });

        // Cascade delete of child photos triggers their R2 cleanup (the DB FK
        // is cascade too, but we delete via the model so R2 objects are removed).
        static::deleting(function (Booking $booking) {
            $booking->photos()->get()->each->delete();
        });
    }

    /* ── Relationships ── */

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(BookingPhoto::class)->orderBy('sort_order');
    }

    /* ── Source helpers ── */

    public function isAdminBooking(): bool
    {
        return $this->source === self::SOURCE_ADMIN;
    }

    /**
     * Statuses this booking may be moved to. Admin bookings are money already
     * collected for a finished session, so the customer-journey statuses
     * (new/contacted/cancelled) don't apply to them.
     */
    public function allowedStatuses(): array
    {
        return $this->isAdminBooking()
            ? [self::STATUS_CONFIRMED, self::STATUS_COMPLETED]
            : self::STATUSES;
    }

    /* ── Delivery helpers ── */

    /**
     * Generate a unique, non-guessable booking number (retry on collision).
     */
    public static function generateBookingNumber(): string
    {
        do {
            $number = self::NUMBER_PREFIX.self::randomString(self::NUMBER_LENGTH);
        } while (static::where('booking_number', $number)->exists());

        return $number;
    }

    /**
     * Generate a fresh delivery access code (shared with the tourist).
     */
    public static function generateDeliveryCode(): string
    {
        return self::randomString(self::CODE_LENGTH);
    }

    protected static function randomString(int $length): string
    {
        $alphabet = self::SAFE_ALPHABET;
        $max = strlen($alphabet) - 1;
        $out = '';

        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }

        return $out;
    }

    public function hasDelivery(): bool
    {
        return ! empty($this->delivery_code) && $this->photos()->exists();
    }

    /**
     * Locale-aware public URL for the tourist photo-delivery page.
     */
    public function getMyPhotosUrlAttribute(): string
    {
        return localized_url(current_locale(), '/my-photos');
    }

    /* ── Accessors ── */

    public function getFullPhoneAttribute(): string
    {
        return trim($this->country_code.' '.$this->phone);
    }

    public function getFormattedSessionTimeAttribute(): ?string
    {
        if (! $this->session_time || ! preg_match('/^(\d{2}):(\d{2})$/', $this->session_time, $matches)) {
            return $this->session_time;
        }

        $hours = (int) $matches[1];
        $minutes = (int) $matches[2];
        $period = $hours >= 12 ? 'PM' : 'AM';
        $hour12 = $hours % 12 === 0 ? 12 : $hours % 12;

        return sprintf('%02d:%02d %s', $hour12, $minutes, $period);
    }

    public function getSessionDatetimeLabelAttribute(): string
    {
        if (! $this->session_date) {
            return '—';
        }

        $formatted = $this->session_date->format('M j, Y');

        return $this->session_time ? "{$formatted} · {$this->formatted_session_time}" : $formatted;
    }

    public function getSourceLabelAttribute(): string
    {
        return $this->isAdminBooking() ? 'By Admin' : 'By Tourist';
    }

    public function getSourceColorAttribute(): string
    {
        return $this->isAdminBooking()
            ? 'bg-sharm-desert/15 text-sharm-desert'
            : 'bg-sharm-sea/10 text-sharm-sea';
    }

    /**
     * Money collected, e.g. "$150" or "E£1,500.50" — decimals only when needed.
     */
    public function getAmountLabelAttribute(): string
    {
        if ($this->amount === null) {
            return '—';
        }

        $amount = (float) $this->amount;
        $symbol = config("currencies.booking.{$this->currency}.symbol", $this->currency);
        $decimals = fmod($amount, 1.0) === 0.0 ? 0 : 2;

        return $symbol.number_format($amount, $decimals);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'New',
            self::STATUS_CONTACTED => 'Contacted',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'bg-blue-50 text-blue-600',
            self::STATUS_CONTACTED => 'bg-amber-50 text-amber-600',
            self::STATUS_CONFIRMED => 'bg-emerald-50 text-emerald-600',
            self::STATUS_COMPLETED => 'bg-sharm-sea/10 text-sharm-sea',
            self::STATUS_CANCELLED => 'bg-red-50 text-red-500',
            default => 'bg-sharm-sand text-sharm-mist',
        };
    }

    /* ── Scopes ── */

    public function scopeLatest($query)
    {
        return $query->orderByDesc('created_at');
    }
}
