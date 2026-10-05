<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class SafeImage implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('Invalid image upload.');

            return;
        }

        $dimensions = @getimagesize($value->getRealPath());
        if (! $dimensions || $dimensions[0] < 1 || $dimensions[1] < 1) {
            $fail('The image dimensions could not be read. Please re-save the image and try again.');

            return;
        }

        [$width, $height] = $dimensions;
        $pixels = $width * $height;
        if ($width > config('photo_delivery.max_image_edge') || $height > config('photo_delivery.max_image_edge')
            || $pixels > config('photo_delivery.max_pixels')) {
            $fail('The image resolution is too large. Resize it to at most '.number_format(config('photo_delivery.max_pixels') / 1_000_000, 0).' megapixels before uploading.');

            return;
        }

        $limit = ini_parse_quantity(ini_get('memory_limit'));
        $ceiling = config('photo_delivery.decode_memory_ceiling');
        $limit = $limit > 0 ? min($limit, $ceiling) : $ceiling;
        $estimated = $pixels * config('photo_delivery.decode_bytes_per_pixel') + $value->getSize() * 2;
        if ($estimated + memory_get_usage(true) + config('photo_delivery.memory_reserve_bytes') > $limit) {
            $fail('This image is too large for the server image-processing budget. Resize it before uploading.');
        }
    }
}
