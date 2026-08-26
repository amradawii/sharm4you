<?php

use App\Http\Controllers\BookSessionController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PhotoDeliveryController;
use App\Http\Controllers\ReviewController;
use App\Models\Album;
use Illuminate\Support\Facades\Route;

Route::redirect('/en', '/', 301);
Route::get('/en/{any}', fn (string $any) => redirect('/'.$any, 301))->where('any', '.*');

Route::middleware('locale')->group(function () {
    Route::get('/', [HomeController::class, 'home'])->name('home');

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
    Route::post('/my-photos/download-all', [PhotoDeliveryController::class, 'downloadAll'])
        ->middleware('throttle:3,1')
        ->name('my-photos.download-all');
});

Route::prefix('{locale}')
    ->whereIn('locale', locale_prefixes())
    ->middleware('locale')
    ->group(function () {
        Route::get('/', [HomeController::class, 'home']);

        Route::get('/about', [PageController::class, 'about']);
        Route::get('/services', [PageController::class, 'services']);

        Route::get('/gallery', [GalleryController::class, 'index']);
        // Wrap show() in a closure so the {locale} prefix parameter isn't
        // passed positionally to the controller (which expects only Album).
        Route::get('/gallery/{album:slug}', function (string $locale, Album $album) {
            return app(GalleryController::class)->show($album);
        });

        Route::post('/book-session', [BookSessionController::class, 'store'])
            ->middleware('throttle:3,1');

        Route::post('/review', [ReviewController::class, 'store'])
            ->middleware('throttle:3,1');

        Route::get('/my-photos', [PhotoDeliveryController::class, 'form']);
        Route::post('/my-photos', [PhotoDeliveryController::class, 'show'])
            ->middleware('throttle:5,1');
        Route::post('/my-photos/download-all', [PhotoDeliveryController::class, 'downloadAll'])
            ->middleware('throttle:3,1');
    });
