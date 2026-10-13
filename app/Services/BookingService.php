<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\HotelStatus;
use App\Exceptions\InvalidBookingException;
use App\Exceptions\InvalidVoucherException;
use App\Exceptions\RoomNotAvailableException;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Promotion;
use App\Models\PromotionUsage;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Alur pembuatan booking.
 *
 * Jaminan anti double booking:
 *  1. seluruh proses berada di dalam DB::transaction();
 *  2. baris `room_types` dikunci dengan lockForUpdate() sehingga permintaan
 *     lain untuk tipe kamar yang sama harus menunggu transaksi selesai;
 *  3. ketersediaan dihitung ULANG setelah kunci didapat, bukan memakai hasil
 *     pengecekan sebelum transaksi.
 *
 * Harga selalu dihitung di server (PricingService); nilai apa pun yang dikirim
 * dari form diabaikan.
 */
class BookingService
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
        private readonly PromotionService $promotions,
    ) {}

    /**
     * Membuat booking baru berstatus pending_payment.
     *
     * @param  array{
     *     check_in: string, check_out: string, guests: int,
     *     guest_name: string, guest_phone: string,
     *     rooms_count?: int, special_request?: ?string, voucher_code?: ?string
     * }  $data
     *
     * @throws InvalidBookingException|RoomNotAvailableException|InvalidVoucherException
     */
    public function create(User $guest, RoomType $roomType, array $data): Booking
    {
        $checkIn = Carbon::parse($data['check_in'])->startOfDay();
        $checkOut = Carbon::parse($data['check_out'])->startOfDay();
        $roomsCount = (int) ($data['rooms_count'] ?? 1);
        $guests = (int) $data['guests'];

        $this->assertDateRange($checkIn, $checkOut);

        if ($roomsCount < 1) {
            throw new InvalidBookingException('Jumlah kamar minimal 1.');
        }

        if ($guests < 1) {
            throw new InvalidBookingException('Jumlah tamu minimal 1 orang.');
        }

        return DB::transaction(function () use ($guest, $roomType, $data, $checkIn, $checkOut, $roomsCount, $guests) {
            // 1) Kunci baris tipe kamar (serialisasi booking pada tipe kamar yang sama).
            $locked = RoomType::withoutGlobalScopes()
                ->whereKey($roomType->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->trashed() || ! $locked->is_active) {
                throw new InvalidBookingException('Tipe kamar ini tidak tersedia untuk dipesan.');
            }

            $hotel = $locked->publicHotel()->first();

            if (! $hotel || $hotel->status !== HotelStatus::Active) {
                throw new InvalidBookingException('Hotel ini sedang tidak menerima pemesanan.');
            }

            if ($guests > $locked->capacity * $roomsCount) {
                throw new InvalidBookingException(
                    'Jumlah tamu melebihi kapasitas kamar (maksimal '.($locked->capacity * $roomsCount).' orang).'
                );
            }

            // 2) Hitung ulang ketersediaan DI DALAM transaksi, setelah baris terkunci.
            $available = $this->availability->availableRooms($locked, $checkIn, $checkOut);

            if ($available < $roomsCount) {
                throw new RoomNotAvailableException(
                    $available < 1
                        ? 'Maaf, kamar sudah penuh untuk tanggal yang dipilih.'
                        : "Maaf, hanya tersisa {$available} kamar untuk tanggal yang dipilih."
                );
            }

            // 3) Harga dihitung ulang di server.
            $nights = (int) $checkIn->diffInDays($checkOut);
            $subtotal = $this->pricing->subtotal($locked, $checkIn, $checkOut, $roomsCount);
            $nightlyRates = $this->pricing->nightlyRates($locked, $checkIn, $checkOut);

            // 4) Promosi: voucher yang dimasukkan tamu, atau promo otomatis terbaik.
            //    Voucher divalidasi DALAM transaksi (kuota & pemakaian per tamu).
            $promotion = $this->resolvePromotion(
                $guest, $hotel, $locked, $checkIn, $checkOut, $subtotal, $nights, $nightlyRates,
                $data['voucher_code'] ?? null
            );

            $voucherCode = $promotion && filled($data['voucher_code'] ?? null)
                ? $this->promotions->normalizeCode($data['voucher_code'])
                : null;

            // 5) Kunci baris promo & naikkan pemakaian. Bila kuota habis di saat kunci
            //    didapat, booking TIDAK dibuat sama sekali (rollback) — voucher melempar
            //    exception, promo otomatis dilewati tanpa diskon.
            if ($promotion) {
                $promotion = $this->lockAndUsePromotion(
                    $promotion,
                    $guest,
                    isThrowingVoucher: $voucherCode !== null
                );

                if (! $promotion) {
                    $voucherCode = null;
                }
            }

            $discount = $promotion
                ? $this->promotions->discountFor($promotion, $subtotal, $nights, $nightlyRates)
                : 0.0;

            $booking = Booking::create([
                'code' => $this->generateCode($checkIn),
                'user_id' => $guest->getKey(),
                'hotel_id' => $hotel->getKey(),
                'room_type_id' => $locked->getKey(),
                'guest_name' => $data['guest_name'],
                'guest_phone' => $data['guest_phone'],
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'nights' => $nights,
                'guests' => $guests,
                'rooms_count' => $roomsCount,
                'subtotal' => $subtotal,
                'promotion_id' => $promotion?->getKey(),
                'voucher_code' => $voucherCode,
                'discount_amount' => $discount,
                'total_price' => round($subtotal - $discount, 2),
                'special_request' => $data['special_request'] ?? null,
                'status' => BookingStatus::PendingPayment,
                'expires_at' => now()->addHours($this->expiryHours()),
            ]);

            if ($promotion) {
                PromotionUsage::create([
                    'promotion_id' => $promotion->getKey(),
                    'booking_id' => $booking->getKey(),
                    'user_id' => $guest->getKey(),
                    'discount_amount' => $discount,
                    'used_at' => now(),
                ]);
            }

            return $booking;
        });
    }

    /**
     * Menentukan promo yang dipakai: voucher tamu bila diisi, selain itu promo
     * otomatis terbaik (tanpa kuota_per_user pembatas tamu).
     */
    private function resolvePromotion(
        User $guest,
        Hotel $hotel,
        RoomType $roomType,
        Carbon $checkIn,
        Carbon $checkOut,
        float $subtotal,
        int $nights,
        array $nightlyRates,
        mixed $voucherCode,
    ): ?Promotion {
        $code = $this->promotions->normalizeCode($voucherCode);

        if ($code !== '') {
            return $this->promotions->validateVoucher(
                $code, $hotel, $roomType, $checkIn, $checkOut, $subtotal, $nights, $guest
            );
        }

        $best = $this->promotions->bestAutomaticFor($roomType, $checkIn, $checkOut, $subtotal, $nights, $nightlyRates);

        return $best['promotion'] ?? null;
    }

    /**
     * Mengunci baris promo, memeriksa ulang kuota & batas pemakaian per tamu,
     * lalu menaikkan pemakaian.
     *
     * @return Promotion|null promo yang benar-benar terpakai; null bila dilewati
     *
     * @throws InvalidVoucherException bila voucher tiba-tiba tidak tersedia
     */
    private function lockAndUsePromotion(Promotion $promotion, User $guest, bool $isThrowingVoucher): ?Promotion
    {
        $locked = Promotion::withoutGlobalScopes()
            ->whereKey($promotion->getKey())
            ->lockForUpdate()
            ->first();

        if (! $locked) {
            return $isThrowingVoucher
                ? throw new InvalidVoucherException('Voucher ini sudah tidak tersedia.')
                : null;
        }

        if (! $locked->is_active || ! $locked->hasQuotaLeft()) {
            return $isThrowingVoucher
                ? throw new InvalidVoucherException('Voucher ini sudah mencapai batas pemakaian.')
                : null;
        }

        $used = $locked->usages()->where('user_id', $guest->getKey())->count();

        if ($used >= max(1, (int) $locked->quota_per_user)) {
            return $isThrowingVoucher
                ? throw new InvalidVoucherException('Anda sudah memakai voucher ini sebelumnya.')
                : null;
        }

        $locked->increment('used_count');

        return $locked->refresh();
    }

    /**
     * Membatalkan booking milik tamu.
     *
     * Mengembalikan stok kamar (status cancelled tidak lagi menahan stok) dan
     * kuota promo yang terpakai.
     *
     * @throws InvalidBookingException bila status saat ini tidak bisa dibatalkan
     */
    public function cancel(Booking $booking, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $reason) {
            $locked = Booking::withoutGlobalScopes()
                ->whereKey($booking->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                throw new InvalidBookingException('Booking tidak ditemukan.');
            }

            if (! $locked->status->cancellable()) {
                throw new InvalidBookingException('Booking dengan status ini tidak dapat dibatalkan.');
            }

            if ($locked->check_in->isPast()) {
                throw new InvalidBookingException('Booking tidak dapat dibatalkan karena hari check-in sudah lewat.');
            }

            $this->releasePromotionQuota($locked);

            $locked->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ]);

            return $locked->refresh();
        });
    }

    /**
     * Menandai booking pending_payment yang melewati batas bayar sebagai expired.
     *
     * @return int jumlah booking yang diubah
     */
    public function expireOverdue(mixed $now = null): int
    {
        $now = $now === null ? now() : Carbon::parse($now);
        $expired = 0;

        Booking::withoutGlobalScopes()
            ->where('status', BookingStatus::PendingPayment->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->orderBy('id')
            ->chunkById(100, function ($bookings) use (&$expired, $now) {
                foreach ($bookings as $booking) {
                    if ($this->expireOne($booking->getKey(), $now)) {
                        $expired++;
                    }
                }
            });

        return $expired;
    }

    /** Mengubah satu booking menjadi expired di dalam transaksi + kunci baris. */
    private function expireOne(int $bookingId, Carbon $now): bool
    {
        return DB::transaction(function () use ($bookingId, $now) {
            $booking = Booking::withoutGlobalScopes()
                ->whereKey($bookingId)
                ->lockForUpdate()
                ->first();

            // Sudah dibayar/dibatalkan proses lain sebelum kunci didapat.
            if (! $booking || $booking->status !== BookingStatus::PendingPayment) {
                return false;
            }

            if ($booking->expires_at === null || $booking->expires_at->isAfter($now)) {
                return false;
            }

            $this->releasePromotionQuota($booking);

            $booking->update(['status' => BookingStatus::Expired]);

            return true;
        });
    }

    /**
     * Mengembalikan kuota promo yang terpakai oleh booking yang dibatalkan
     * atau kedaluwarsa.
     *
     * Baris promotion_usages dihapus (bukan sekadar ditandai) agar `used_count`
     * tetap sama dengan jumlah pemakaian aktif dan `quota_per_user` milik tamu
     * tidak ikut terpakai oleh booking yang gagal.
     */
    private function releasePromotionQuota(Booking $booking): void
    {
        $usage = $booking->promotionUsage()->first();

        if (! $usage) {
            return;
        }

        $promotion = Promotion::withoutGlobalScopes()
            ->whereKey($usage->promotion_id)
            ->lockForUpdate()
            ->first();

        if ($promotion) {
            $promotion->update(['used_count' => max(0, $promotion->used_count - 1)]);
        }

        $usage->delete();
    }

    /** Kode booking unik berformat {prefiks}-YYMMDD-XXXXXX. */
    private function generateCode(Carbon $checkIn): string
    {
        $prefix = (string) config('booking.code_prefix', 'BK');

        do {
            $code = $prefix.'-'.$checkIn->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Booking::withoutGlobalScopes()->where('code', $code)->exists());

        return $code;
    }

    /** Validasi rentang tanggal tanpa menyentuh database. */
    private function assertDateRange(Carbon $checkIn, Carbon $checkOut): void
    {
        if ($checkIn->isBefore(today())) {
            throw new InvalidBookingException('Tanggal check-in tidak boleh sebelum hari ini.');
        }

        if ($checkOut->lessThanOrEqualTo($checkIn)) {
            throw new InvalidBookingException('Tanggal check-out harus setelah tanggal check-in.');
        }
    }

    private function expiryHours(): int
    {
        return max(1, (int) config('booking.payment_expiry_hours', 24));
    }
}
