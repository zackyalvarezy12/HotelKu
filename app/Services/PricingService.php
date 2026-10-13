<?php

namespace App\Services;

use App\Models\RoomType;
use Illuminate\Support\Carbon;

/**
 * Perhitungan harga menginap.
 *
 * Malam dihitung per tanggal: malam pertama = tanggal check-in, dan tanggal
 * check-out tidak dihitung sebagai malam. Bila sebuah malam jatuh di dalam
 * rentang harga musiman, harga musiman menggantikan harga dasar.
 */
class PricingService
{
    /**
     * Harga per malam untuk seluruh malam menginap.
     *
     * @return array<string, float> [Y-m-d => harga per malam]
     */
    public function nightlyRates(RoomType $roomType, mixed $checkIn, mixed $checkOut): array
    {
        $start = Carbon::parse($checkIn)->startOfDay();
        $end = Carbon::parse($checkOut)->startOfDay();

        $seasonal = $roomType->seasonalPrices()
            ->orderBy('start_date')
            ->get(['start_date', 'end_date', 'price']);

        $basePrice = (float) $roomType->base_price;
        $rates = [];

        for ($night = $start->copy(); $night->lt($end); $night->addDay()) {
            $price = $basePrice;

            foreach ($seasonal as $period) {
                if ($night->betweenIncluded($period->start_date, $period->end_date)) {
                    $price = (float) $period->price;
                    break; // periode pertama yang cocok dipakai
                }
            }

            $rates[$night->toDateString()] = $price;
        }

        return $rates;
    }

    /** Subtotal = (jumlah harga semua malam) x jumlah kamar. */
    public function subtotal(RoomType $roomType, mixed $checkIn, mixed $checkOut, int $roomsCount = 1): float
    {
        $perNight = array_sum($this->nightlyRates($roomType, $checkIn, $checkOut));

        return round($perNight * max(0, $roomsCount), 2);
    }
}
