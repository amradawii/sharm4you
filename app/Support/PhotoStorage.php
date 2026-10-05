<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class PhotoStorage
{
    public static function put(string $disk, string $path, mixed $contents): void
    {
        try {
            self::checked($disk, 'write', fn () => Storage::disk($disk)->put($path, $contents));
        } catch (HttpException $e) {
            self::cleanup($disk, $path);
            throw $e;
        }
    }

    public static function delete(string $disk, string $path, bool $directory = false): void
    {
        self::checked($disk, 'delete', fn () => $directory ? Storage::disk($disk)->deleteDirectory($path) : Storage::disk($disk)->delete($path));
    }

    public static function move(string $disk, string $source, string $destination): void
    {
        self::checked($disk, 'move', fn () => Storage::disk($disk)->move($source, $destination));
    }

    public static function makeDirectory(string $disk, string $path): void
    {
        self::checked($disk, 'directory', fn () => Storage::disk($disk)->makeDirectory($path));
    }

    public static function cleanup(string $disk, ?string $path, bool $directory = false): bool
    {
        if (! $path) {
            return true;
        }
        try {
            self::delete($disk, $path, $directory);

            return true;
        } catch (HttpException $e) {
            return false;
        }
    }

    public static function restoreMove(string $disk, string $source, string $destination): bool
    {
        try {
            if (Storage::disk($disk)->exists($destination)) {
                throw new RuntimeException('Restore destination already exists.');
            }
            self::move($disk, $source, $destination);

            return true;
        } catch (Throwable $e) {
            Log::error('Photo directory restoration failed.', ['disk' => $disk, 'exception' => $e::class]);

            return false;
        }
    }

    private static function checked(string $disk, string $operation, callable $callback): void
    {
        try {
            if ($callback() !== true) {
                throw new RuntimeException('Storage operation returned false.');
            }
        } catch (Throwable $e) {
            Log::error('Photo storage operation failed.', ['disk' => $disk, 'operation' => $operation, 'exception' => $e::class]);
            throw new HttpException(503, 'Photo storage is temporarily unavailable. Please try again.');
        }
    }
}
