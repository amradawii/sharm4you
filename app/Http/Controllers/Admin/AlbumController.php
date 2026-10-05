<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAlbumRequest;
use App\Http\Requests\Admin\UpdateAlbumRequest;
use App\Models\Album;
use App\Support\PhotoStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class AlbumController extends Controller
{
    /**
     * Album list page.
     */
    public function index()
    {
        $albums = Album::withCount('photos')
            ->with('coverPhoto.album')
            ->ordered()
            ->get();

        $albumsJson = $albums->map(fn (Album $album) => [
            'id' => $album->id,
            'name_en' => $album->name_en,
            'name_ru' => $album->name_ru,
            'name_it' => $album->name_it,
            'name_ro' => $album->name_ro,
            'name_bg' => $album->name_bg,
            'name_tr' => $album->name_tr,
            'slug' => $album->slug,
            'is_active' => $album->is_active,
            'photos_count' => $album->photos_count,
            'cover_url' => $album->cover_url,
            'edit_url' => route('admin.gallery.photos', $album),
        ])->values();

        return view('admin.gallery.index', compact('albums', 'albumsJson'));
    }

    /**
     * Create a new album (AJAX).
     */
    public function store(StoreAlbumRequest $request): JsonResponse
    {
        $createdPath = null;
        try {
            $album = DB::transaction(function () use ($request, &$createdPath) {
                $album = Album::create([...$request->validated(), 'sort_order' => Album::max('sort_order') + 1]);

                // Create the storage directory
                $path = $album->storagePath();
                if (Storage::disk('public')->exists($path)) {
                    throw ValidationException::withMessages(['name_en' => 'An album folder already exists at this name. Choose a different name or contact the developer.']);
                }
                $createdPath = $path;
                PhotoStorage::makeDirectory('public', $path);

                return $album;
            });
        } catch (Throwable $e) {
            PhotoStorage::cleanup('public', $createdPath, directory: true);
            throw $e;
        }

        $album->loadCount('photos');

        return response()->json([
            'success' => true,
            'message' => 'Album created successfully.',
            'album' => [
                'id' => $album->id,
                'name_en' => $album->name_en,
                'name_ru' => $album->name_ru,
                'name_it' => $album->name_it,
                'name_ro' => $album->name_ro,
                'name_bg' => $album->name_bg,
                'name_tr' => $album->name_tr,
                'slug' => $album->slug,
                'is_active' => $album->is_active,
                'photos_count' => 0,
                'cover_url' => null,
                'edit_url' => route('admin.gallery.photos', $album),
            ],
        ]);
    }

    /**
     * Update album details (AJAX).
     */
    public function update(UpdateAlbumRequest $request, Album $album): JsonResponse
    {
        $oldPath = $album->storagePath();
        $newPath = null;
        $moved = false;
        try {
            $album = DB::transaction(function () use ($request, $album, &$oldPath, &$newPath, &$moved) {
                $album = Album::query()->lockForUpdate()->findOrFail($album->id);
                $oldPath = $album->storagePath();
                $newSlug = Album::generateUniqueSlug($request->input('name_en'), $album->id);
                $album->fill([...$request->validated(), 'slug' => $newSlug]);
                $newPath = $album->storagePath();

                // Rename storage directory if slug changed
                if ($oldPath !== $newPath && Storage::disk('public')->exists($oldPath)) {
                    if (Storage::disk('public')->exists($newPath)) {
                        throw ValidationException::withMessages(['name_en' => 'An album folder already exists at this name. Choose a different name or contact the developer.']);
                    }
                    PhotoStorage::move('public', $oldPath, $newPath);
                    $moved = true;
                }
                $album->save();

                return $album;
            });
        } catch (Throwable $e) {
            if ($moved) {
                PhotoStorage::restoreMove('public', $newPath, $oldPath);
            }
            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Album updated successfully.',
            'album' => [
                'id' => $album->id,
                'name_en' => $album->name_en,
                'name_ru' => $album->name_ru,
                'name_it' => $album->name_it,
                'name_ro' => $album->name_ro,
                'name_bg' => $album->name_bg,
                'name_tr' => $album->name_tr,
                'slug' => $album->slug,
                'cover_url' => $album->cover_url,
            ],
        ]);
    }

    /**
     * Toggle album active status (AJAX).
     */
    public function toggleActive(Album $album): JsonResponse
    {
        $album->update(['is_active' => ! $album->is_active]);

        return response()->json([
            'success' => true,
            'message' => $album->is_active ? 'Album is now visible.' : 'Album is now hidden.',
            'is_active' => $album->is_active,
        ]);
    }

    /**
     * Delete album and all its photos (AJAX).
     */
    public function destroy(Album $album): JsonResponse
    {
        // Delete all files from storage
        PhotoStorage::delete('public', $album->storagePath(), directory: true);

        // Cascade delete handles DB photos
        $album->delete();

        return response()->json([
            'success' => true,
            'message' => 'Album deleted successfully.',
        ]);
    }
}
