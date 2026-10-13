<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\DiscountType;
use App\Enums\PromotionType;
use App\Exceptions\InvalidVoucherException;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Promotion;
use App\Models\PromotionUsage;
use App\Models\RoomType;
use App\Models\User;
use App\Services\PromotionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Validasi kode voucher dan perhitungan potongan promo. */
final class PromotionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PromotionService $service;

    private ?Hotel $currentHotel = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->service = app(PromotionService::class);
    }

    private function createHotel(): Hotel
    {
        $this->currentHotel = Hotel::factory()->create([
            'owner_id' => $this->createUserWithoutGuard('owner')->id,
        ]);

        $this->createUserWithoutGuard('guest');

        return $this->currentHotel;
    }

    private function createUser(string $role): User
    {
        return User::factory()->{$role}()->create();
    }

    private function createUserWithoutGuard(string $role): User
    {
        return User::factory()->{$role}()->create();
    }

    private function promotion(array $overrides = []): Promotion
    {
        $hotel = $this->currentHotel ?? $this->createHotel();

        return Promotion::factory()->create(array_merge([
            'hotel_id' => $hotel->id,
            'type' => PromotionType::Voucher,
            'code' => 'TEST-123',
            'discount_type' => DiscountType::Fixed,
            'discount_value' => 100000,
            'min_nights' => 1,
            'min_total' => 0,
            'quota' => 10,
            'quota_per_user' => 1,
            'used_count' => 0,
            'is_active' => true,
            'is_public' => true,
        ], $overrides));
    }

    private function roomTypes(Hotel $hotel, array $overrides = []): RoomType
    {
        return RoomType::factory()->create(array_merge([
            'hotel_id' => $hotel->id,
            'base_price' => 500000,
            'capacity' => 2,
            'total_rooms' => 3,
            'is_active' => true,
        ], $overrides));
    }

    /** ----------------------------------------------------------------- */
    /* KODE VOUCHER                                                       */
    /** ----------------------------------------------------------------- */

    public function test_valid_kode_dikembalikan(): void
    {
        $hotel = $this->createHotel();
        $code = 'TEST-123';
        $this->promotion(['code' => $code]);

        $result = $this->service->validateVoucher(
            $code,
            $hotel,
            $this->roomTypes($hotel),
            Carbon::parse('2026-10-18'),
            Carbon::parse('2026-10-20'),
            1500000,
            2,
            $this->createUser('guest'),
        );

        $this->assertSame($code, $result->code);
        $this->assertSame(PromotionType::Voucher, $result->type);
    }

    public function test_kode_tidak_ditemukan_throws(): void
    {
        $hotel = $this->createHotel();

        $this->expectException(InvalidVoucherException::class);
        $this->expectExceptionMessage('Voucher tidak ditemukan');

        $this->service->validateVoucher(
            'SALAH-000',
            $hotel,
            $this->roomTypes($hotel),
            Carbon::parse('2026-10-18'),
            Carbon::parse('2026-10-20'),
            1500000,
            2,
            $this->createUser('guest'),
        );
    }

    public function test_kode_bukan_voucher_throws(): void
    {
        $hotel = $this->createHotel();
        $this->promotion([
            'type' => PromotionType::AutoDiscount,
            'code' => 'NOT-VOUCHER',
        ]);

        $this->expectException(InvalidVoucherException::class);
        $this->expectExceptionMessage('bukan voucher');

        $this->service->validateVoucher(
            'NOT-VOUCHER',
            $hotel,
            $this->roomTypes($hotel),
            Carbon::parse('2026-10-18'),
            Carbon::parse('2026-10-20'),
            1500000,
            2,
            $this->createUser('guest'),
        );
    }

    public function test_kode_tidak_aktif_throws(): void
    {
        $hotel = $this->createHotel();
        $this->promotion(['is_active' => false]);

        $this->expectException(InvalidVoucherException::class);
        $this->expectExceptionMessage('sedang tidak aktif');

        $this->service->validateVoucher(
            'TEST-123',
            $hotel,
            $this->roomTypes($hotel),
            Carbon::parse('2026-10-18'),
            Carbon::parse('2026-10-20'),
            1500000,
            2,
            $this->createUser('guest'),
        );
    }

    public function test_kode_bukan_untuk_tipe_kamar_yang_dipesan_throws(): void
    {
        $hotel = $this->createHotel();
        $promotion = $this->promotion(['quota' => 10]);
        $roomType = $this->roomTypes($hotel);
        $otherRoomType = $this->roomTypes($hotel, ['id' => 9999]);

        // promo tidak berlaku untuk otherRoomType
        $promotion->roomTypes()->attach($otherRoomType);

        $this->expectException(InvalidVoucherException::class);
        $this->expectExceptionMessage('tidak berlaku untuk tipe kamar');

        $this->service->validateVoucher(
            'TEST-123',
            $hotel,
            $roomType,
            Carbon::parse('2026-10-18'),
            Carbon::parse('2026-10-20'),
            1500000,
            2,
            $this->createUser('guest'),
        );
    }

    public function test_night_min_malamin_throws(): void
    {
        $hotel = $this->createHotel();
        $this->promotion(['min_nights' => 3]);

        $this->expectException(InvalidVoucherException::class);
        $this->expectExceptionMessage('minimal menginap 3');

        $this->service->validateVoucher(
            'TEST-123',
            $hotel,
            $this->roomTypes($hotel),
            Carbon::parse('2026-10-18'),
            Carbon::parse('2026-10-19'),
            1500000,
            1,
            $this->createUser('guest'),
        );
    }

    public function test_min_total_throws(): void
    {
        $hotel = $this->createHotel();
        $this->promotion(['min_total' => 2000000]);

        $this->expectException(InvalidVoucherException::class);
        $this->expectExceptionMessage('total pemesanan minimal Rp2.000.000');

        $this->service->validateVoucher(
            'TEST-123',
            $hotel,
            $this->roomTypes($hotel),
            Carbon::parse('2026-10-18'),
            Carbon::parse('2026-10-20'),
            1500000,
            2,
            $this->createUser('guest'),
        );
    }

    public function test_kuota_habis_throws(): void
    {
        $hotel = $this->createHotel();
        $this->promotion(['quota' => 1, 'used_count' => 1]);

        $this->expectException(InvalidVoucherException::class);
        $this->expectExceptionMessage('sudah mencapai batas pemakaian');

        $this->service->validateVoucher(
            'TEST-123',
            $hotel,
            $this->roomTypes($hotel),
            Carbon::parse('2026-10-18'),
            Carbon::parse('2026-10-20'),
            1500000,
            2,
            $this->createUser('guest'),
        );
    }

    public function test_kuota_per_user_dikosongi_throws(): void
    {
        $hotel = $this->createHotel();
        $promotion = $this->promotion(['quota_per_user' => 1]);
        $roomType = $this->roomTypes($hotel);

        $guest1 = $this->createUser('guest');
        $booking = Booking::factory()->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomType->id,
            'user_id' => $guest1->id,
            'promotion_id' => $promotion->id,
            'voucher_code' => 'TEST-123',
        ]);
        PromotionUsage::create([
            'promotion_id' => $promotion->id,
            'booking_id' => $booking->id,
            'user_id' => $guest1->id,
            'discount_amount' => 100000,
            'used_at' => now(),
        ]);

        $this->expectException(InvalidVoucherException::class);
        $this->expectExceptionMessage('sudah pernah memakai voucher');

        $this->service->validateVoucher(
            'TEST-123',
            $hotel,
            $this->roomTypes($hotel),
            Carbon::parse('2026-10-18'),
            Carbon::parse('2026-10-20'),
            1500000,
            2,
            $guest1,
        );
    }

    public function test_kode_case_insensitive(): void
    {
        $hotel = $this->createHotel();
        $this->promotion(['code' => 'Test-123']);

        $result = $this->service->validateVoucher(
            'test-123',
            $hotel,
            $this->roomTypes($hotel),
            Carbon::parse('2026-10-18'),
            Carbon::parse('2026-10-20'),
            1500000,
            2,
            $this->createUser('guest'),
        );

        $this->assertSame('Test-123', $result->code);
    }

    /** ----------------------------------------------------------------- */
    /* PERHITUNGAN POTONGAN                                               */
    /** ----------------------------------------------------------------- */

    public function test_diskon_persen_dikurangi_dari_subtotal(): void
    {
        $hotel = $this->createHotel();
        $promotion = $this->promotion([
            'discount_type' => DiscountType::Percent,
            'discount_value' => 20,
            'min_nights' => 1,
        ]);

        $discount = $this->service->discountFor(
            $promotion,
            1500000,
            2,
            [],   // tarif tidak dipakai untuk percent
        );

        $this->assertSame(300000.0, $discount);
    }

    public function test_diskon_tidak_lebihi_subtotal(): void
    {
        $hotel = $this->createHotel();
        $promotion = $this->promotion([
            'discount_type' => DiscountType::Fixed,
            'discount_value' => 200000,
            'min_nights' => 1,
        ]);

        $discount = $this->service->discountFor(
            $promotion,
            100000,
            1,
            [],
        );

        $this->assertSame(100000.0, $discount);
    }

    public function test_diskon_fixed_kanaan(): void
    {
        $hotel = $this->createHotel();
        $promotion = $this->promotion([
            'discount_type' => DiscountType::Fixed,
            'discount_value' => 250000,
            'min_nights' => 1,
        ]);

        $discount = $this->service->discountFor(
            $promotion,
            100000,
            1,
            [],
        );

        $this->assertSame(100000.0, $discount); // tidak boleh melebihi subtotal
    }

    public function test_free_night_dikembalikan_dengan_tarif(): void
    {
        $hotel = $this->createHotel();
        $roomType = $this->roomTypes($hotel);

        $promotion = $this->promotion([
            'discount_type' => DiscountType::FreeNight,
            'discount_value' => 1,
            'min_nights' => 2,
        ]);

        $rates = [500000.0, 500000.0];

        $discount = $this->service->discountFor(
            $promotion,
            1500000,
            2,
            $rates,
        );

        $this->assertSame(500000.0, $discount);
    }

    public function test_free_night_tidak_muncul_saat_night_satu(): void
    {
        $hotel = $this->createHotel();
        $roomType = $this->roomTypes($hotel);

        $promotion = $this->promotion([
            'discount_type' => DiscountType::FreeNight,
            'discount_value' => 1,
            'min_nights' => 2,
        ]);

        $rates = [500000.0, 500000.0];

        $discount = $this->service->discountFor(
            $promotion,
            1500000,
            1,
            $rates,
        );

        $this->assertSame(0.0, $discount);
    }

    /** ----------------------------------------------------------------- */
    /* PERIODE & HARI                                                     */
    /** ----------------------------------------------------------------- */

    public function test_voucher_hanya_berlaku_saat_stay(): void
    {
        $hotel = $this->createHotel();
        $this->promotion([
            'stay_start' => '2026-11-01',
            'stay_end' => '2026-11-10',
            'min_nights' => 1,
        ]);

        $this->expectException(InvalidVoucherException::class);
        $this->expectExceptionMessage('hanya berlaku untuk menginap mulai');

        $this->service->validateVoucher(
            'TEST-123',
            $hotel,
            $this->roomTypes($hotel),
            Carbon::parse('2026-10-18'),
            Carbon::parse('2026-10-20'),
            1500000,
            2,
            $this->createUser('guest'),
        );
    }

    public function test_voucher_ditolak_bila_check_out_melewati_stay_end(): void
    {
        $hotel = $this->createHotel();
        $this->promotion([
            'stay_start' => '2026-10-01',
            'stay_end' => '2026-10-07',
            'min_nights' => 1,
        ]);

        $this->expectException(InvalidVoucherException::class);
        $this->expectExceptionMessage('check-out melewati akhir periode');

        $this->service->validateVoucher(
            'TEST-123',
            $hotel,
            $this->roomTypes($hotel),
            Carbon::parse('2026-10-01'),
            Carbon::parse('2026-10-09'),
            1500000,
            8,
            $this->createUser('guest'),
        );
    }

    public function test_voucher_berlaku_setiap_hari_kalau_tidak_diisi(): void
    {
        $hotel = $this->createHotel();
        $this->promotion(['valid_days' => null]);

        $result = $this->service->validateVoucher(
            'TEST-123',
            $hotel,
            $this->roomTypes($hotel),
            Carbon::parse('2026-10-18'),
            Carbon::parse('2026-10-20'),
            1500000,
            2,
            $this->createUser('guest'),
        );

        $this->assertSame('TEST-123', $result->code);
    }

    public function test_promo_otomatis_tanpa_pivot_berlaku_untuk_semua_tipe_kamar(): void
    {
        $hotel = $this->createHotel();
        $roomType = $this->roomTypes($hotel);
        $promotion = $this->promotion([
            'hotel_id' => $hotel->id,
            'type' => PromotionType::AutoDiscount,
            'code' => null,
            'discount_type' => DiscountType::Percent,
            'discount_value' => 20,
            'min_nights' => 1,
        ]);

        $best = $this->service->bestAutomaticFor(
            $roomType,
            Carbon::parse('2026-10-18'),
            Carbon::parse('2026-10-19'),
            500000,
            1,
            ['2026-10-18' => 500000],
        );

        $this->assertSame($promotion->id, $best['promotion']->id);
        $this->assertSame(100000.0, $best['discount']);
    }

    public function test_harga_coret_detail_dan_pencarian_memakai_promo_terbaik(): void
    {
        $hotel = $this->createHotel();
        $roomType = $this->roomTypes($hotel);
        $promotion = $this->promotion([
            'type' => PromotionType::AutoDiscount,
            'code' => null,
            'discount_type' => DiscountType::Percent,
            'discount_value' => 20,
            'min_nights' => 1,
        ]);
        $checkIn = Carbon::parse('2026-10-18');
        $checkOut = Carbon::parse('2026-10-19');

        $detailPrice = $this->service
            ->displayPricesForRoomTypes(collect([$roomType]), $checkIn, $checkOut)
            ->get($roomType->id);
        $searchPrice = $this->service
            ->displayPricesForHotels(collect([$hotel]), collect([$roomType]), $checkIn, $checkOut)
            ->get($hotel->id);

        $this->assertSame(500000.0, $detailPrice['original']);
        $this->assertSame(400000.0, $detailPrice['discounted']);
        $this->assertSame($promotion->id, $detailPrice['promotion']->id);
        $this->assertSame(500000.0, $searchPrice['original']);
        $this->assertSame(400000.0, $searchPrice['discounted']);
        $this->assertSame($promotion->id, $searchPrice['promotion']->id);
    }
}
