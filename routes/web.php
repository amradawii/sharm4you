<?php

use App\Http\Controllers\BookSessionController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PhotoDeliveryController;
use App\Http\Controllers\ReviewController;
use App\Models\Package;
use App\Models\Photo;
use App\Models\Review;
use Illuminate\Support\Facades\Route;

Route::redirect('/en', '/', 301);
Route::get('/en/{any}', fn (string $any) => redirect('/'.$any, 301))->where('any', '.*');

Route::middleware('locale')->group(function () {
    Route::get('/', function () {
        $featuredPhotos = Photo::with('album')
            ->featured()
            ->ordered()
            ->limit(12)
            ->get();

        $packages = Package::active()->forLocale()->ordered()->get();
        $packagesSlots = Package::slotsMap($packages);

        $testimonials = Review::approved()
            ->featured()
            ->ordered()
            ->limit(Review::FEATURED_LIMIT)
            ->get();

        return view('home', compact('featuredPhotos', 'packages', 'packagesSlots', 'testimonials'));
    })->name('home');

    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/services', [PageController::class, 'services'])->name('services');

    Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery');
    Route::get('/gallery/{album:slug}', [GalleryController::class, 'show'])->name('gallery.album');

    Route::post('/book-session', [BookSessionController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('book-session');

    Route::post('/review', [ReviewController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('review.store');

    Route::get('/my-photos', [PhotoDeliveryController::class, 'form'])->name('my-photos');
    Route::post('/my-photos', [PhotoDeliveryController::class, 'show'])
        ->middleware('throttle:5,1')
        ->name('my-photos.lookup');
});

Route::prefix('{locale}')
    ->whereIn('locale', locale_prefixes())
    ->middleware('locale')
    ->group(function () {
        Route::get('/', function () {
            $featuredPhotos = Photo::with('album')
                ->featured()
                ->ordered()
                ->limit(12)
                ->get();

            $packages = Package::active()->forLocale()->ordered()->get();
            $packagesSlots = Package::slotsMap($packages);

            $testimonials = Review::approved()
                ->featured()
                ->ordered()
                ->limit(Review::FEATURED_LIMIT)
                ->get();

            return view('home', compact('featuredPhotos', 'packages', 'packagesSlots', 'testimonials'));
        });

        Route::get('/about', [PageController::class, 'about']);
        Route::get('/services', [PageController::class, 'services']);

        Route::get('/gallery', [GalleryController::class, 'index']);
        Route::get('/gallery/{album:slug}', [GalleryController::class, 'show']);

        Route::post('/book-session', [BookSessionController::class, 'store'])
            ->middleware('throttle:3,1');

        Route::post('/review', [ReviewController::class, 'store'])
            ->middleware('throttle:3,1');

        Route::get('/my-photos', [PhotoDeliveryController::class, 'form']);
        Route::post('/my-photos', [PhotoDeliveryController::class, 'show'])
            ->middleware('throttle:5,1');
    });
