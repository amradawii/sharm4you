<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BookingPhoto extends Model
{
    /**
     * Presigned download links stay valid for this window. The delivery code
     * itself never expires — a fresh link is generated on every page load.
     */
    public const LINK_TTL_HOURS = 24;

    protected $fillable = [
        'booking_id',
        'r2_key',
        'original_name',
        'size',
        'sort_order',
    ];

    protected $casts = [
        'size' => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        // Remove the object from R2 when the row is deleted so we never leak
        // (and pay for) orphaned storage. Runs for both single deletes and
        // cascade deletes triggered from the Booking model.
        static::deleting(function (BookingPhoto $photo) {
            if ($photo->r2_key) {
                Storage::disk('r2')->delete($photo->r2_key);
            }
        });
    }

    /* ── Relationships ── */

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /* ── Accessors ── */

    /**
     * Time-limited presigned URL for private download from R2.
     */
    public function getDownloadUrlAttribute(): string
    {
        return Storage::disk('r2')->temporaryUrl(
            $this->r2_key,
            now()->addHours(self::LINK_TTL_HOURS)
        );
    }

    public function getSizeLabelAttribute(): string
    {
        return self::formatBytes($this->size);
    }

    /* ── Helpers ── */

    /**
     * Total bytes currently stored across all delivery photos (for the meter).
     */
    public static function totalBytes(): int
    {
        return (int) static::sum('size');
    }

    public static function formatBytes(int $bytes, int $decimals = 1): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), $decimals).' '.$units[$power];
    }
}
