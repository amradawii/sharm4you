<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Photo;
use App\Models\Review;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function home(): View
    {
        $featuredPhotos = Photo::with('album')
            ->publicFeatured()
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
    }
}
