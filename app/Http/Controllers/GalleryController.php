<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Photo;

class GalleryController extends Controller
{
    /**
     * Public gallery — show active albums.
     */
    public function index()
    {
        $albums = Album::active()
            ->ordered()
            ->withCount('photos')
            ->with('coverPhoto.album')
            ->get();

        $featuredPhotos = Photo::with('album')
            ->featured()
            ->ordered()
            ->limit(12)
            ->get();

        return view('gallery', compact('albums', 'featuredPhotos'));
    }

    /**
     * Public gallery — show a single album's photos.
     */
    public function show(Album $album)
    {
        if (! $album->is_active) {
            abort(404);
        }

        // Eager-load album so Photo::getUrlAttribute() (reads $photo->album->slug)
        // doesn't trigger one extra query per photo on the album page.
        $photos = $album->photos()->with('album')->ordered()->get();

        return view('gallery-album', compact('album', 'photos'));
    }
}
