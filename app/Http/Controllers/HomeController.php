<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /** Beranda: form pencarian, kota populer, dan hotel pilihan (hanya hotel aktif). */
    public function index(): View
    {
        $cities = Hotel::query()
            ->publiclyVisible()
            ->selectRaw('city, COUNT(*) AS total')
            ->groupBy('city')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $featured = Hotel::query()
            ->forListing()
            ->orderByDesc('star_rating')
            ->orderBy('name')
            ->limit(6)
            ->get();

        return view('home', [
            'cities' => $cities,
            'featured' => $featured,
            'totalHotels' => Hotel::query()->publiclyVisible()->count(),
        ]);
    }
}
