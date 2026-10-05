<?php

use App\Http\Controllers\Admin\AlbumController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\PhotoController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware('admin.locale')->group(function () {

    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:admin-login')
            ->name('login.post');
    });

    Route::middleware('auth.admin')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'updateInfo'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // Gallery — Albums
        Route::get('/gallery', [AlbumController::class, 'index'])->name('gallery.index');
        Route::post('/gallery', [AlbumController::class, 'store'])->name('gallery.store');
        Route::put('/gallery/{album}', [AlbumController::class, 'update'])->name('gallery.update');
        Route::delete('/gallery/{album}', [AlbumController::class, 'destroy'])->name('gallery.destroy');
        Route::patch('/gallery/{album}/toggle', [AlbumController::class, 'toggleActive'])->name('gallery.toggle');

        // Gallery — Photos
        Route::get('/gallery/{album}/photos', [PhotoController::class, 'index'])->name('gallery.photos');
        Route::post('/gallery/{album}/photos', [PhotoController::class, 'upload'])->name('gallery.photos.upload');
        Route::put('/gallery/photos/{photo}', [PhotoController::class, 'update'])->name('gallery.photos.update');
        Route::delete('/gallery/photos/{photo}', [PhotoController::class, 'destroy'])->name('gallery.photos.destroy');
        Route::patch('/gallery/photos/{photo}/featured', [PhotoController::class, 'toggleFeatured'])->name('gallery.photos.featured');

        // Packages (tier = one offering across EN/IT/RU locales)
        Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
        Route::post('/packages', [PackageController::class, 'store'])->name('packages.store');
        Route::put('/packages/tier/{hours}', [PackageController::class, 'update'])->where('hours', '[0-9]+(?:\.[0-9]+)?')->name('packages.tier.update');
        Route::patch('/packages/tier/{hours}/toggle', [PackageController::class, 'toggleTier'])->where('hours', '[0-9]+(?:\.[0-9]+)?')->name('packages.tier.toggle');
        Route::patch('/packages/tier/{hours}/featured', [PackageController::class, 'toggleFeaturedTier'])->where('hours', '[0-9]+(?:\.[0-9]+)?')->name('packages.tier.featured');
        Route::delete('/packages/tier/{hours}', [PackageController::class, 'destroyTier'])->where('hours', '[0-9]+(?:\.[0-9]+)?')->name('packages.tier.destroy');

        // Bookings — no delete route on purpose: bookings are financial records
        // and stay append-only (corrections happen via edit or status).
        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
        Route::put('/bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update');
        Route::patch('/bookings/{booking}/status', [BookingController::class, 'updateStatus'])->name('bookings.status');
        Route::put('/bookings/{booking}/notes', [BookingController::class, 'updateNotes'])->name('bookings.notes');

        // Bookings — photo delivery (R2)
        Route::post('/bookings/{booking}/photos', [BookingController::class, 'uploadPhoto'])->name('bookings.photos.upload');
        Route::delete('/bookings/photos/{photo}', [BookingController::class, 'destroyPhoto'])->name('bookings.photos.destroy');
        Route::patch('/bookings/{booking}/delivery', [BookingController::class, 'generateDelivery'])->name('bookings.delivery');

        // Reviews
        Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
        Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
        Route::patch('/reviews/{review}/status', [ReviewController::class, 'setStatus'])->name('reviews.status');
        Route::patch('/reviews/{review}/featured', [ReviewController::class, 'toggleFeatured'])->name('reviews.featured');
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    });
});
