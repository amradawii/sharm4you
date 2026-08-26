<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UploadPhotosRequest;
use App\Models\Album;
use App\Models\Photo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

class PhotoController extends Controller
{
    /**
     * Album detail page (photo management).
     */
    public function index(Album $album)
    {
        $album->load(['photos' => fn ($q) => $q->ordered()]);
        $featuredCount = Photo::featuredCount();

        $photosJson = $album->photos->map(fn (Photo $photo) => [
            'id'            => $photo->id,
            'filename'      => $photo->filename,
            'original_name' => $photo->original_name,
            'alt_en'        => $photo->alt_en,
            'alt_ru'        => $photo->alt_ru,
            'alt_it'        => $photo->alt_it,
            'alt_de'        => $photo->alt_de,
            'alt_uk'        => $photo->alt_uk,
            'is_featured'   => $photo->is_featured,
            'url'           => $photo->url,
        ])->values();

        return view('admin.gallery.show', compact('album', 'featuredCount', 'photosJson'));
    }

    /**
     * Bulk upload photos (AJAX, max 5 per request).
     */
    public function upload(UploadPhotosRequest $request, Album $album): JsonResponse
    {
        $manager = new ImageManager(new GdDriver());
        $uploaded = [];
        $maxSort = $album->photos()->max('sort_order') ?? 0;

        foreach ($request->file('photos') as $file) {
            // Extra security: verify real MIME via finfo
            $realMime = $file->getMimeType();
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];

            if (! in_array($realMime, $allowedMimes, true)) {
                continue; // skip invalid files silently
            }

            // Generate unique filename
            $uuid = Str::uuid()->toString();
            $filename = "{$uuid}.webp";
            $storagePath = "{$album->storagePath()}/{$filename}";

            // Convert to WebP via Intervention/Image GD driver
            $image = $manager->read($file->getRealPath());
            $encoded = $image->toWebp(quality: 90);

            Storage::disk('public')->put($storagePath, (string) $encoded);

            // Create DB record
            $maxSort++;
            $originalName = $file->getClientOriginalName();

            $photo = Photo::create([
                'album_id'      => $album->id,
                'filename'      => $filename,
                'original_name' => $originalName,
                'alt_en'        => pathinfo($originalName, PATHINFO_FILENAME),
                'sort_order'    => $maxSort,
            ]);

            // Set as cover if album has no cover
            if (! $album->cover_photo_id) {
                $album->update(['cover_photo_id' => $photo->id]);
            }

            $uploaded[] = [
                'id'            => $photo->id,
                'filename'      => $photo->filename,
                'original_name' => $photo->original_name,
                'alt_en'        => $photo->alt_en,
                'alt_ru'        => $photo->alt_ru,
                'alt_it'        => $photo->alt_it,
                'alt_de'        => $photo->alt_de,
                'alt_uk'        => $photo->alt_uk,
                'is_featured'   => $photo->is_featured,
                'url'           => asset("storage/{$storagePath}"),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => count($uploaded) . ' photo(s) uploaded successfully.',
            'photos'  => $uploaded,
            'photos_count' => $album->photos()->count(),
        ]);
    }

    /**
     * Toggle featured status (AJAX).
     */
    public function toggleFeatured(Photo $photo): JsonResponse
    {
        if (! $photo->is_featured) {
            // Check global limit
            $currentCount = Photo::featuredCount();

            if ($currentCount >= 12) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum 12 featured photos reached. Unfeature another photo first.',
                ], 422);
            }
        }

        $photo->update(['is_featured' => ! $photo->is_featured]);

        return response()->json([
            'success'        => true,
            'message'        => $photo->is_featured ? 'Photo featured.' : 'Photo unfeatured.',
            'is_featured'    => $photo->is_featured,
            'featured_count' => Photo::featuredCount(),
        ]);
    }

    /**
     * Update photo alt texts (AJAX).
     */
    public function update(Request $request, Photo $photo): JsonResponse
    {
        $validated = $request->validate([
            'alt_en' => ['nullable', 'string', 'max:500'],
            'alt_ru' => ['nullable', 'string', 'max:500'],
            'alt_it' => ['nullable', 'string', 'max:500'],
            'alt_de' => ['nullable', 'string', 'max:500'],
            'alt_uk' => ['nullable', 'string', 'max:500'],
        ]);

        $photo->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Alt text updated.',
        ]);
    }

    /**
     * Delete a single photo (AJAX).
     */
    public function destroy(Photo $photo): JsonResponse
    {
        $album = $photo->album;

        // Delete file from storage
        Storage::disk('public')->delete("{$album->storagePath()}/{$photo->filename}");

        // If this was the album cover, reset
        if ($album->cover_photo_id === $photo->id) {
            $album->update(['cover_photo_id' => null]);
        }

        $photo->delete();

        // Set a new cover if there are remaining photos
        if (! $album->cover_photo_id) {
            $newCover = $album->photos()->first();
            if ($newCover) {
                $album->update(['cover_photo_id' => $newCover->id]);
            }
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Photo deleted.',
            'photos_count' => $album->photos()->count(),
        ]);
    }
}
