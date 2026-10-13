<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use App\Services\PromotionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class HotelController extends Controller
{
    /**
     * Detail hotel untuk tamu.
     *
     * Pencarian record dilakukan manual (bukan route model binding) agar global
     * scope isolasi pemilik tidak ikut berlaku, dan agar hotel non-aktif
     * (pending/suspended) otomatis 404.
     */
    public function show(Request $request, string $hotel, AvailabilityService $availability): View
    {
        $model = Hotel::query()
            ->publiclyVisible()
            ->with([
                'photos' => fn ($q) => $q->orderByDesc('is_cover')->orderBy('sort_order'),
                'facilities',
                'roomTypes' => fn ($q) => $q->publiclyVisible()
                    ->with(['photos' => fn ($p) => $p->orderBy('sort_order'), 'facilities', 'seasonalPrices']),
            ])
            ->where('slug', $hotel)
            ->firstOrFail();

        // Tanggal opsional dari halaman pencarian, dipakai untuk sisa kamar.
        $checkIn = $request->query('masuk');
        $checkOut = $request->query('keluar');
        $guests = max(1, (int) $request->query('tamu', 2));

        $hasDates = filled($checkIn) && filled($checkOut);

        $remainingRooms = $hasDates
            ? $availability->availabilityForHotel($model, $checkIn, $checkOut)
            : collect();
        $reviews = $model->reviews()->with('user')->latest()->limit(8)->get();
        $ratingAverage = round((float) $model->reviews()->avg('rating'), 1);

        return view('hotels.show', [
            'hotel' => $model,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'guests' => $guests,
            'hasDates' => $hasDates,
            'remainingRooms' => $remainingRooms,
            'reviews' => $reviews,
            'ratingAverage' => $ratingAverage,
            'prices' => $model->roomTypes->isNotEmpty()
                ? $this->displayPrices($model, $checkIn, $checkOut)
                : collect(),
        ]);
    }

    /**
     * Harga tampilan per tipe kamar hotel (halaman detail).
     *
     * Satu query promo untuk seluruh hotel yang diminta, lalu pilih promo
     * terbaik per tipe kamar. Mengganti private `displayPromotions` yang
     * mengulang logika `PromotionService`.
     *
     * @return Collection<int, array{room_type_id: int, original: float, discounted: float|null, promotion: Promotion|null}>
     */
    private function displayPrices(
        Hotel $hotel,
        ?string $checkIn,
        ?string $checkOut,
        ?Collection $roomTypes = null,
    ): Collection {
        $roomTypes = $roomTypes ?? $hotel->roomTypes->where('is_public', true);

        return app(PromotionService::class)->displayPricesForRoomTypes(
            $roomTypes,
            Carbon::parse($checkIn ?: now())->startOfDay(),
            Carbon::parse($checkOut ?: now()->addDay())->startOfDay(),
        );
    }
}
