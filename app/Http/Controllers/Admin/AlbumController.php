<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAlbumRequest;
use App\Http\Requests\Admin\UpdateAlbumRequest;
use App\Models\Album;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            'id'           => $album->id,
            'name_en'      => $album->name_en,
            'name_ru'      => $album->name_ru,
            'name_it'      => $album->name_it,
            'name_ro'      => $album->name_ro,
            'name_bg'      => $album->name_bg,
            'name_tr'      => $album->name_tr,
            'slug'         => $album->slug,
            'is_active'    => $album->is_active,
            'photos_count' => $album->photos_count,
            'cover_url'    => $album->cover_url,
            'edit_url'     => route('admin.gallery.photos', $album),
        ])->values();

        return view('admin.gallery.index', compact('albums', 'albumsJson'));
    }

    /**
     * Create a new album (AJAX).
     */
    public function store(StoreAlbumRequest $request): JsonResponse
    {
        $album = Album::create([
            'name_en'    => $request->input('name_en'),
            'name_ru'    => $request->input('name_ru'),
            'name_it'    => $request->input('name_it'),
            'name_ro'    => $request->input('name_ro'),
            'name_bg'    => $request->input('name_bg'),
            'name_tr'    => $request->input('name_tr'),
            'sort_order' => Album::max('sort_order') + 1,
        ]);

        // Create the storage directory
        Storage::disk('public')->makeDirectory($album->storagePath());

        $album->loadCount('photos');

        return response()->json([
            'success' => true,
            'message' => 'Album created successfully.',
            'album'   => [
                'id'           => $album->id,
                'name_en'      => $album->name_en,
                'name_ru'      => $album->name_ru,
                'name_it'      => $album->name_it,
                'name_ro'      => $album->name_ro,
                'name_bg'      => $album->name_bg,
                'name_tr'      => $album->name_tr,
                'slug'         => $album->slug,
                'is_active'    => $album->is_active,
                'photos_count' => 0,
                'cover_url'    => null,
                'edit_url'     => route('admin.gallery.photos', $album),
            ],
        ]);
    }

    /**
     * Update album details (AJAX).
     */
    public function update(UpdateAlbumRequest $request, Album $album): JsonResponse
    {
        $oldSlug = $album->slug;
        $newSlug = Album::generateUniqueSlug($request->input('name_en'), $album->id);

        $album->update([
            'name_en' => $request->input('name_en'),
            'name_ru' => $request->input('name_ru'),
            'name_it' => $request->input('name_it'),
            'name_ro' => $request->input('name_ro'),
            'name_bg' => $request->input('name_bg'),
            'name_tr' => $request->input('name_tr'),
            'slug'    => $newSlug,
        ]);

        // Rename storage directory if slug changed
        if ($oldSlug !== $newSlug && Storage::disk('public')->exists("albums/{$oldSlug}")) {
            Storage::disk('public')->move("albums/{$oldSlug}", "albums/{$newSlug}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Album updated successfully.',
            'album'   => [
                'id'      => $album->id,
                'name_en' => $album->name_en,
                'name_ru' => $album->name_ru,
                'name_it' => $album->name_it,
                'name_ro' => $album->name_ro,
                'name_bg' => $album->name_bg,
                'name_tr' => $album->name_tr,
                'slug'    => $album->slug,
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
            'success'   => true,
            'message'   => $album->is_active ? 'Album is now visible.' : 'Album is now hidden.',
            'is_active' => $album->is_active,
        ]);
    }

    /**
     * Delete album and all its photos (AJAX).
     */
    public function destroy(Album $album): JsonResponse
    {
        // Delete all files from storage
        Storage::disk('public')->deleteDirectory($album->storagePath());

        // Cascade delete handles DB photos
        $album->delete();

        return response()->json([
            'success' => true,
            'message' => 'Album deleted successfully.',
        ]);
    }
}
