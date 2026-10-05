<?php

namespace App\Models;

use App\Support\PhotoStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

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
        'filename',
        'size',
        'sort_order',
    ];

    protected $casts = [
        'size' => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (BookingPhoto $photo) {
            $filename = $photo->filename;
            if (! is_string($filename) || trim($filename) === '' || mb_strlen($filename) > 255
                || str_contains($filename, '/') || str_contains($filename, '\\') || preg_match('/\p{C}/u', $filename)) {
                throw ValidationException::withMessages(['filename' => 'The delivery filename is invalid.']);
            }
        });

        // Remove the object from R2 when the row is deleted so we never leak
        // (and pay for) orphaned storage. Runs for both single deletes and
        // cascade deletes triggered from the Booking model.
        static::deleting(function (BookingPhoto $photo) {
            if ($photo->r2_key) {
                PhotoStorage::delete('r2', $photo->r2_key);
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
        $name = $this->filename;
        $fallback = preg_replace('/[^A-Za-z0-9._ -]/', '_', Str::ascii($name));

        return Storage::disk('r2')->temporaryUrl(
            $this->r2_key,
            now()->addHours(self::LINK_TTL_HOURS),
            [
                'ResponseContentDisposition' => HeaderUtils::makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $name, $fallback),
                'ResponseCacheControl' => 'private, no-store',
            ]
        );
    }

    public function getPreviewUrlAttribute(): string
    {
        return Storage::disk('r2')->temporaryUrl($this->r2_key, now()->addHours(self::LINK_TTL_HOURS), [
            'ResponseContentDisposition' => 'inline',
            'ResponseCacheControl' => 'private, no-store',
        ]);
    }

    public function getSizeLabelAttribute(): string
    {
        return self::formatBytes($this->size);
    }

    /* ── Scopes ── */

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
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
        $power = (int) floor(log($bytes, 1000));
        $power = min($power, count($units) - 1);

        return round($bytes / (1000 ** $power), $decimals).' '.$units[$power];
    }
}
