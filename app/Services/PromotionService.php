<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\PromotionType;
use App\Exceptions\InvalidVoucherException;
use App\Models\Hotel;
use App\Models\Promotion;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Aturan bisnis promosi: validasi penerapan voucher dan perhitungan potongan.
 *
 * Perhitungan diskon selalu memakai subtotal yang sudah dihitung server
 * (PricingService) — tidak pernah nilai dari peramban.
 *
 * Batas potongan:
 *  - `percent`     : persen dari subtotal, dibatasi `max_discount` bila diisi.
 *  - `fixed`       : nominal tetap, tidak boleh melebihi subtotal.
 *  - `free_night`  : gratis 1 malam dengan tarif termurah × jumlah kamar
 *                    (hanya bila menginap minimal 2 malam).
 */
class PromotionService
{
    public function __construct(private readonly PricingService $pricing) {}

    /** Membuang kode yang tidak wajar agar aman dipakai di klausa where. */
    public function normalizeCode(?string $code): string
    {
        return mb_strtoupper(trim((string) $code));
    }

    /* ---------------------------------------------------------------------
     | Validasi voucher
     * -------------------------------------------------------------------- */

    /**
     * Memastikan kode voucher boleh dipakai untuk pemesanan ini.
     *
     * @throws InvalidVoucherException
     */
    public function validateVoucher(
        ?string $code,
        Hotel $hotel,
        RoomType $roomType,
        Carbon $checkIn,
        Carbon $checkOut,
        float $subtotal,
        int $nights,
        User $guest,
    ): Promotion {
        $normalized = $this->normalizeCode($code);

        if ($normalized === '') {
            throw new InvalidVoucherException('Kode voucher tidak boleh kosong.');
        }

        // Kode unik per hotel (lihat migrasi promotions), jadi pencarian dibatasi
        // pada hotel yang sedang dipesan agar kode hotel lain tidak bisa dipakai.
        $promotion = Promotion::withoutGlobalScopes()
            ->where('hotel_id', $hotel->getKey())
            ->whereRaw('UPPER(code) = ?', [$normalized])
            ->first();

        if (! $promotion) {
            throw new InvalidVoucherException('Voucher tidak ditemukan. Periksa kembali kodenya.');
        }

        if ($promotion->type !== PromotionType::Voucher) {
            throw new InvalidVoucherException('Kode tersebut bukan voucher.');
        }

        if (! $promotion->is_active) {
            throw new InvalidVoucherException('Voucher ini sedang tidak aktif.');
        }

        if (! $promotion->appliesToRoomType($roomType->getKey())) {
            throw new InvalidVoucherException('Voucher ini tidak berlaku untuk tipe kamar yang dipilih.');
        }

        $this->assertBookingPeriod($promotion);
        $this->assertStayPeriod($promotion, $checkIn, $checkOut);
        $this->assertValidDays($promotion, $checkIn);

        if ($nights < max(1, (int) $promotion->min_nights)) {
            throw new InvalidVoucherException(
                "Voucher ini berlaku untuk minimal menginap {$promotion->min_nights} malam."
            );
        }

        if ($subtotal < (float) $promotion->min_total) {
            throw new InvalidVoucherException(
                'Voucher ini berlaku untuk total pemesanan minimal '
                .'Rp'.number_format((float) $promotion->min_total, 0, ',', '.').'.'
            );
        }

        if (! $promotion->hasQuotaLeft()) {
            throw new InvalidVoucherException('Voucher ini sudah mencapai batas pemakaian.');
        }

        $this->assertPerUserQuota($promotion, $guest);

        return $promotion;
    }

    private function assertBookingPeriod(Promotion $promotion): void
    {
        $today = today();

        if ($promotion->booking_start && $today->lt($promotion->booking_start)) {
            throw new InvalidVoucherException(
                'Voucher ini baru berlaku mulai '.$promotion->booking_start->translatedFormat('d M Y').'.'
            );
        }

        if ($promotion->booking_end && $today->gt($promotion->booking_end)) {
            throw new InvalidVoucherException('Periode pemesanan voucher ini sudah berakhir.');
        }
    }

    private function assertStayPeriod(Promotion $promotion, Carbon $checkIn, Carbon $checkOut): void
    {
        if ($promotion->stay_start && $checkIn->lt($promotion->stay_start)) {
            throw new InvalidVoucherException(
                'Voucher ini hanya berlaku untuk menginap mulai '
                .$promotion->stay_start->translatedFormat('d M Y').'.'
            );
        }

        // Menginap sebagian melewati akhir periode tetap tidak diizinkan.
        if ($promotion->stay_end && $checkOut->copy()->subDay()->gt($promotion->stay_end)) {
            throw new InvalidVoucherException(
                'Tanggal check-out melewati akhir periode voucher ('
                .$promotion->stay_end->translatedFormat('d M Y').').'
            );
        }
    }

    private function assertValidDays(Promotion $promotion, Carbon $checkIn): void
    {
        if (! $promotion->isValidOnDay($checkIn)) {
            $names = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $allowed = collect((array) $promotion->valid_days)
                ->map(fn ($day) => $names[(int) $day] ?? (string) $day)
                ->implode(', ');

            throw new InvalidVoucherException("Voucher ini hanya berlaku pada hari: {$allowed}.");
        }
    }

    private function assertPerUserQuota(Promotion $promotion, User $guest): void
    {
        $limit = max(1, (int) $promotion->quota_per_user);

        $used = $promotion->usages()->where('user_id', $guest->getKey())->count();

        if ($used >= $limit) {
            throw new InvalidVoucherException(
                $limit === 1
                    ? 'Anda sudah pernah memakai voucher ini sebelumnya.'
                    : "Anda sudah memakai voucher ini {$used} kali dari kuota {$limit} kali."
            );
        }
    }

    /* ---------------------------------------------------------------------
     | Perhitungan potongan
     * -------------------------------------------------------------------- */

    /**
     * Menghitung potongan untuk sebuah promo terhadap subtotal.
     *
     * @param  array<string, float>  $nightlyRates  tarif per malam (1 kamar)
     * @return float  potongan dalam rupiah; tidak pernah negatif atau melebihi subtotal
     */
    public function discountFor(Promotion $promotion, float $subtotal, int $nights, array $nightlyRates = []): float
    {
        if ($subtotal <= 0 || $nights < 1) {
            return 0.0;
        }

        $discount = match ($promotion->discount_type) {
            DiscountType::Percent => $subtotal * ((float) $promotion->discount_value / 100),
            DiscountType::Fixed => (float) $promotion->discount_value,
            DiscountType::FreeNight => $this->freeNightDiscount($promotion, $nights, $nightlyRates),
        };

        if ($promotion->max_discount !== null) {
            $discount = min($discount, (float) $promotion->max_discount);
        }

        return round(min(max(0.0, $discount), $subtotal), 2);
    }

    /** Gratis 1 malam = tarif termurah × jumlah kamar. Tanpa data tarif => 0. */
    private function freeNightDiscount(Promotion $promotion, int $nights, array $nightlyRates): float
    {
        if ($nights < 2 || $nightlyRates === []) {
            return 0.0;
        }

        return round(min($nightlyRates) * max(1, (int) ($promotion->discount_value ?: 1)), 2);
    }

    /* ---------------------------------------------------------------------
     | Promo otomatis & harga coret
     * -------------------------------------------------------------------- */

    /**
     * Promosi otomatis yang berlaku untuk hotel, terpilah per hotel.
     *
     * Filter kriteria berlaku sepanjang stay (booking window + stay window
     * + min/max nights). Tidak memakai global scope, sehingga detail hotel
     * (owner-only) tetap bisa membaca promo yang terkunci ke hotelnya.
     *
     * Output: Collection<string hotelId, Collection<int, Promotion>>,
     *   cuma promo yang memenuhi semua syarat untuk minimal 1 malam.
     */
    private function runningAutomaticPromotionsForHotels(array $hotelIds, Carbon $checkIn, Carbon $checkOut): Collection
    {
        $today = today()->toDateString();

        return Promotion::withoutGlobalScopes()
            ->whereIn('hotel_id', $hotelIds)
            ->where('is_active', true)
            ->where('is_public', true)
            ->where('type', '!=', PromotionType::Voucher->value)
            ->with('roomTypes')
            ->where(function ($query) use ($today) {
                $query->whereNull('booking_start')->orWhere('booking_start', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('booking_end')->orWhere('booking_end', '>=', $today);
            })
            ->where(function ($query) use ($checkIn) {
                $query->whereNull('stay_start')->orWhere('stay_start', '<=', $checkIn->toDateString());
            })
            ->where(function ($query) use ($checkOut) {
                $query->whereNull('stay_end')->orWhere('stay_end', '>=', $checkOut->toDateString());
            })
            ->get()
            ->filter(fn ($promotion) => $promotion->hasQuotaLeft())
            ->groupBy('hotel_id');
    }

    /**
     * Promo otomatis yang berlaku pada tipe kamar & rentang tanggal ini.
     *
     * Mengecek berlakuan kriteria tsb sepanjang stay, jadi "otomatis"
     * untuk pemesanan yang masuk rentang tersebut (termasuk malam pertama).
     *
     * @return Collection<int, Promotion>
     */
    public function automaticFor(RoomType $roomType, Carbon $checkIn, Carbon $checkOut): Collection
    {
        return $this->runningAutomaticPromotionsForHotels([
            $roomType->hotel_id,
        ], $checkIn, $checkOut)
            ->get($roomType->hotel_id, collect())
            ->filter(fn ($promotion) => $promotion->appliesToRoomType($roomType->getKey()))
            ->filter(fn ($promotion) => $promotion->isValidOnDay($checkIn));
    }

    /**
     * Potongan terbesar dari promo otomatis, atau null bila tidak ada yang berlaku.
     *
     * @return array{promotion: Promotion, discount: float, total: float}|null
     */
    public function bestAutomaticFor(RoomType $roomType, Carbon $checkIn, Carbon $checkOut, float $subtotal, int $nights, array $nightlyRates = []): ?array
    {
        $cheapestPromotion = null;
        $best = null;

        foreach ($this->automaticFor($roomType, $checkIn, $checkOut) as $promotion) {
            if ($subtotal < (float) $promotion->min_total || $nights < max(1, (int) $promotion->min_nights)) {
                continue;
            }

            $discount = $this->discountFor($promotion, $subtotal, $nights, $nightlyRates);

            if ($discount <= 0) {
                continue;
            }

            if ($best === null || $discount > $best['discount']) {
                $best = ['promotion' => $promotion, 'discount' => $discount, 'total' => $subtotal - $discount];
                $cheapestPromotion = $promotion;
            }
        }

        return $best;
    }

    /**
     * Harga per malam & harga coret untuk satu tipe kamar (halaman publik).
     *
     * Menghitung tarif per malam sekali, lalu memilih promo otomatis terbaik
     * untuk satu malam — dipakai untuk menampilkan harga coret di pencarian
     * maupun detail hotel.
     *
     * @return array{original: float, discounted: ?float, promotion: ?Promotion}
     */
    public function displayPriceFor(RoomType $roomType, Carbon $checkIn, Carbon $checkOut): array
    {
        // Tarif per malam (1 kamar) untuk rentang tanggal yang dipilih.
        $rates = $this->pricing->nightlyRates($roomType, $checkIn, $checkIn->copy()->addDay());
        $original = $rates !== [] ? array_sum($rates) : (float) $roomType->base_price;

        $best = $this->bestAutomaticFor($roomType, $checkIn, $checkOut, $original, 1, $rates);

        return [
            'original' => $original,
            'discounted' => $best ? $best['total'] : null,
            'promotion' => $best['promotion'] ?? null,
        ];
    }

    /**
     * Harga tampilan per tipe kamar (halaman detail hotel).
     *
     * Satu query promo untuk seluruh hotel, lalu pilih promo terbaik
     * per tipe kamar. Tidak ada N+1: harga asli + harga coret + promo
     * terbaik dikembalikan per tipe kamar, siap untuk view.
     *
     * @param Collection<int, RoomType> $roomTypes
     * @return Collection<int, array{room_type_id: int, original: float, discounted: float|null, promotion: Promotion|null}>
     */
    public function displayPricesForRoomTypes(Collection $roomTypes, Carbon $checkIn, Carbon $checkOut): Collection
    {
        $hotelIds = $roomTypes->pluck('hotel_id')->unique()->toArray();
        $promotionsByHotel = $this->runningAutomaticPromotionsForHotels($hotelIds, $checkIn, $checkOut);

        return $roomTypes->mapWithKeys(function (RoomType $roomType) use ($promotionsByHotel, $checkIn) {
            $hotelId = $roomType->hotel_id;
            $promotionsForHotel = $promotionsByHotel->get($hotelId, collect());

            // Tarif per malam tipe kamar yang sedang dipilih dihitung sekali.
            $rates = $this->pricing->nightlyRates($roomType, $checkIn, $checkIn->copy()->addDay());
            $original = $rates !== [] ? array_sum($rates) : (float) $roomType->base_price;

            $best = null;
            $bestPromotion = null;

            foreach ($promotionsForHotel as $promotion) {
                if (! $promotion->appliesToRoomType($roomType->getKey())) {
                    continue;
                }

                if (! $promotion->isValidOnDay($checkIn) || ! $promotion->hasQuotaLeft()) {
                    continue;
                }

                if (max(1, (int) $promotion->min_nights) > 1 || $original < (float) $promotion->min_total) {
                    continue;
                }

                $discount = $this->discountFor($promotion, $original, 1, $rates);

                if ($discount <= 0) {
                    continue;
                }

                if ($best === null || $discount > $best) {
                    $best = $discount;
                    $bestPromotion = $promotion;
                }
            }

            return [
                $roomType->getKey() => [
                    'room_type_id' => $roomType->getKey(),
                    'original' => $original,
                    'discounted' => $bestPromotion !== null ? round($original - $best, 2) : null,
                    'promotion' => $bestPromotion,
                ],
            ];
        });
    }

    /**
     * Harga coret untuk banyak tipe kamar sekaligus (halaman pencarian).
     *
     * Dipanggil sekali per halaman pencarian dengan seluruh tipe kamar milik
     * hotel yang sedang ditampilkan, sehingga jumlah query promo tetap konstan
     * berapa pun banyaknya hasil (tanpa N+1).
     *
     * @param  \Illuminate\Support\Collection<int, RoomType>  $roomTypes
     * @param  \Illuminate\Support\Collection<int, Hotel>  $hotels
     * @return \Illuminate\Support\Collection<int, array{original: float, discounted: ?float, promotion: ?Promotion}>
     *         kunci = id hotel
     */
    public function displayPricesForHotels(Collection $hotels, Collection $roomTypes, Carbon $checkIn, Carbon $checkOut): Collection
    {
        if ($hotels->isEmpty()) {
            return collect();
        }

        // Satu query promo untuk seluruh hotel pada halaman ini.
        $promotionsByHotel = $this->runningAutomaticPromotionsForHotels(
            $hotels->pluck('id')->unique()->toArray(),
            $checkIn,
            $checkOut,
        );

        return $hotels->mapWithKeys(function (Hotel $hotel) use ($promotionsByHotel, $roomTypes, $checkIn) {
            // Tipe kamar termurah milik hotel ini (harga "mulai dari" pada kartu).
            $cheapest = $roomTypes
                ->where('hotel_id', $hotel->getKey())
                ->sortBy('base_price')
                ->first();

            // Tarif per malam tipe kamar termurah dihitung sekali, bukan per promo.
            $rates = $cheapest
                ? $this->pricing->nightlyRates($cheapest, $checkIn, $checkIn->copy()->addDay())
                : [];
            $original = $rates !== []
                ? array_sum($rates)
                : (float) ($cheapest?->base_price ?? $hotel->min_price ?? 0);
            $oneNight = $original;

            $best = null;
            $cheapestPromotion = null;

            // Kartu pencarian menampilkan harga per malam, jadi syarat minimal
            // malam dinilai terhadap 1 malam.
            if ($oneNight <= 0) {
                return [$hotel->getKey() => [
                    'original' => $original,
                    'discounted' => null,
                    'promotion' => null,
                ]];
            }

            foreach ($promotionsByHotel->get($hotel->getKey(), collect()) as $promotion) {
                if ($cheapest && ! $promotion->appliesToRoomType($cheapest->getKey())) {
                    continue;
                }

                if (! $promotion->isValidOnDay($checkIn) || ! $promotion->hasQuotaLeft()) {
                    continue;
                }

                if (max(1, (int) $promotion->min_nights) > 1) {
                    continue;
                }

                if ($oneNight < (float) $promotion->min_total) {
                    continue;
                }

                $discount = $this->discountFor($promotion, $oneNight, 1, $rates);

                if ($discount <= 0) {
                    continue;
                }

                if ($best === null || $discount > $best) {
                    $best = $discount;
                    $cheapestPromotion = $promotion;
                }
            }

            return [$hotel->getKey() => [
                'original' => $original,
                'discounted' => $best !== null ? round($original - $best, 2) : null,
                'promotion' => $cheapestPromotion,
            ]];
        });
    }
}
