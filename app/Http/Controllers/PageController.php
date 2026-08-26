<?php

namespace App\Http\Controllers;

use App\Models\Package;

class PageController extends Controller
{
    public function about()
    {
        return view('about');
    }

    public function services()
    {
        $packages = Package::active()->forLocale()->ordered()->get();
        $packagesSlots = Package::slotsMap($packages);

        return view('services', compact('packages', 'packagesSlots'));
    }
}
