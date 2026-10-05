<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use ZipArchive;

class DeliveryArchive
{
    public function create(Collection $photos): string
    {
        $budget = config('photo_delivery.zip_max_bytes');
        if ($photos->count() > config('photo_delivery.max_per_booking') || $photos->sum('size') > $budget) {
            throw new HttpException(422, __('site.my_photos.zip_too_large'));
        }

        $zipPath = null;
        $sources = [];
        $zip = new ZipArchive;
        $opened = false;
        try {
            $zipPath = $this->temporaryFile();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new HttpException(503, __('site.my_photos.zip_failed'));
            }
            $opened = true;
            $total = 0;
            foreach ($photos->values() as $index => $photo) {
                $source = $this->temporaryFile();
                $sources[] = $source;
                $input = null;
                $output = null;
                try {
                    $input = Storage::disk('r2')->readStream($photo->r2_key);
                    $output = fopen($source, 'wb');
                    if (! is_resource($input) || ! is_resource($output)) {
                        throw new HttpException(503, __('site.my_photos.zip_failed'));
                    }
                    $bytes = stream_copy_to_stream($input, $output, $budget - $total + 1);
                    if ($bytes === false) {
                        throw new HttpException(503, __('site.my_photos.zip_failed'));
                    }
                    $total += $bytes;
                    if ($total > $budget) {
                        throw new HttpException(422, __('site.my_photos.zip_too_large'));
                    }
                } finally {
                    if (is_resource($input)) {
                        fclose($input);
                    }
                    if (is_resource($output)) {
                        fclose($output);
                    }
                }

                $entry = sprintf('%02d-%s', $index + 1, $photo->filename);
                if (! $zip->addFile($source, $entry) || ! $zip->setCompressionName($entry, ZipArchive::CM_STORE)) {
                    throw new HttpException(503, __('site.my_photos.zip_failed'));
                }
            }
            if (! $zip->close()) {
                throw new HttpException(503, __('site.my_photos.zip_failed'));
            }
            $opened = false;

            return $zipPath;
        } catch (Throwable $e) {
            if ($opened) {
                try {
                    $zip->close();
                } catch (Throwable) {
                }
            }
            $zip = null;
            $this->removeTemporaryFile($zipPath);
            if ($e instanceof HttpException) {
                throw $e;
            }
            Log::error('Delivery archive preparation failed.', ['exception' => $e::class]);
            throw new HttpException(503, __('site.my_photos.zip_failed'));
        } finally {
            foreach ($sources as $source) {
                $this->removeTemporaryFile($source);
            }
        }
    }

    private function temporaryFile(): string
    {
        $directory = config('photo_delivery.temp_directory');
        if (! is_dir($directory) || ! is_writable($directory)) {
            throw new HttpException(503, __('site.my_photos.zip_failed'));
        }
        $path = tempnam($directory, 'sharm4you_');
        if ($path === false) {
            throw new HttpException(503, __('site.my_photos.zip_failed'));
        }

        return $path;
    }

    private function removeTemporaryFile(?string $path): void
    {
        if ($path && is_file($path) && ! @unlink($path)) {
            Log::error('Delivery temporary-file cleanup failed.');
        }
    }
}
