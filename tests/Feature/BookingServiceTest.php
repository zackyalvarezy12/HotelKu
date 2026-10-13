<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\HotelStatus;
use App\Exceptions\InvalidBookingException;
use App\Exceptions\RoomNotAvailableException;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\SeasonalPrice;
use App\Models\User;
use App\Services\BookingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Alur pembuatan booking: ketersediaan, anti double booking, harga, dan kode. */
class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BookingService $service;

    protected User $owner;

    protected User $guest;

    protected Hotel $hotel;

    protected RoomType $roomType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->service = app(BookingService::class);
        $this->owner = User::factory()->owner()->create();
        $this->guest = User::factory()->guest()->create();

        $this->hotel = Hotel::factory()->create(['owner_id' => $this->owner->id]);
        $this->roomType = RoomType::factory()->create([
            'hotel_id' => $this->hotel->id,
            'base_price' => 500000,
            'capacity' => 2,
            'total_rooms' => 3,
            'is_active' => true,
        ]);
    }

    /** Data booking standar; bisa ditimpa sebagian. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
            'guests' => 2,
            'guest_name' => 'Tamu Uji',
            'guest_phone' => '081200000000',
            'rooms_count' => 1,
        ], $overrides);
    }

    private function book(array $overrides = []): Booking
    {
        return $this->service->create($this->guest, $this->roomType, $this->payload($overrides));
    }

    /* -----------------------------------------------------------------
     | Booking berhasil
     * ----------------------------------------------------------------- */

    public function test_booking_baru_berstatus_pending_payment_dengan_kode_dan_batas_bayar(): void
    {
        $this->freezeTime();

        $booking = $this->book();

        $this->assertSame(BookingStatus::PendingPayment, $booking->status);
        $this->assertTrue($booking->user->is($this->guest));
        $this->assertSame($this->hotel->id, $booking->hotel_id);
        $this->assertSame($this->roomType->id, $booking->room_type_id);
        $this->assertSame(2, $booking->nights);

        // Format kode: BK-YYMMDD-XXXXXX
        $this->assertMatchesRegularExpression('/^BK-\d{6}-[A-Z0-9]{6}$/', $booking->code);

        // Batas bayar = config('booking.payment_expiry_hours') jam dari sekarang.
        $this->assertNotNull($booking->expires_at);
        $this->assertSame(
            now()->addHours((int) config('booking.payment_expiry_hours'))->toDateTimeString(),
            $booking->expires_at->toDateTimeString(),
            'Batas bayar harus '.config('booking.payment_expiry_hours').' jam dari waktu pembuatan booking.'
        );
    }

    public function test_kode_booking_selalu_unik(): void
    {
        // Sepuluh booking pada tanggal yang sama perlu stok yang cukup.
        $this->roomType->update(['total_rooms' => 20]);

        $codes = collect(range(1, 10))
            ->map(fn () => $this->book()['code'])
            ->all();

        $this->assertCount(10, array_unique($codes));
    }

    public function test_total_dihitung_di_server_dan_mengabaikan_nilai_dari_form(): void
    {
        // Nilai total yang "dikirim" klien tidak ada dalam payload yang dipercaya.
        $booking = $this->book([
            'total_price' => 1,          // harus diabaikan
            'subtotal' => 1,             // harus diabaikan
            'nights' => 99,              // harus diabaikan
            'discount_amount' => 999999, // harus diabaikan
        ]);

        $this->assertSame('1000000.00', (string) $booking->subtotal);
        $this->assertSame('0.00', (string) $booking->discount_amount);
        $this->assertSame('1000000.00', (string) $booking->total_price);
        $this->assertSame(2, $booking->nights);
    }

    public function test_harga_musiman_dipakai_untuk_malam_yang_tercakup(): void
    {
        $checkIn = now()->addDays(10)->startOfDay();

        // Rentang harga musiman mencakup tanggal secara inklusif: satu malam saja.
        SeasonalPrice::create([
            'room_type_id' => $this->roomType->id,
            'start_date' => $checkIn->toDateString(),
            'end_date' => $checkIn->toDateString(),
            'price' => 900000,
            'label' => 'High Season',
        ]);

        // Malam ke-1 kena harga musiman (900.000), malam ke-2 harga dasar (500.000).
        $booking = $this->book([
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkIn->copy()->addDays(2)->toDateString(),
        ]);

        $this->assertSame('1400000.00', (string) $booking->subtotal);
    }

    public function test_beberapa_kamar_mengalikan_subtotal(): void
    {
        $booking = $this->book(['rooms_count' => 2]);

        $this->assertSame('2000000.00', (string) $booking->subtotal);
    }

    /* -----------------------------------------------------------------
     | Validasi aturan bisnis
     * ----------------------------------------------------------------- */

    public function test_check_out_harus_setelah_check_in(): void
    {
        $this->expectException(InvalidBookingException::class);

        $this->book([
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(5)->toDateString(),
        ]);
    }

    public function test_check_in_tidak_boleh_masa_lalu(): void
    {
        $this->expectException(InvalidBookingException::class);

        $this->book([
            'check_in' => now()->subDay()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
        ]);
    }

    public function test_jumlah_tamu_tidak_boleh_melebihi_kapasitas(): void
    {
        $this->expectException(InvalidBookingException::class);

        // kapasitas 2 x 1 kamar = 2 orang.
        $this->book(['guests' => 3]);
    }

    public function test_kapasitas_dihitung_dari_jumlah_kamar(): void
    {
        // 2 kamar x kapasitas 2 = 4 orang.
        $booking = $this->book(['guests' => 4, 'rooms_count' => 2]);

        $this->assertSame(4, $booking->guests);
    }

    public function test_tipe_kamar_non_aktif_tidak_bisa_dipesan(): void
    {
        $this->roomType->update(['is_active' => false]);

        $this->expectException(InvalidBookingException::class);

        $this->book();
    }

    public function test_hotel_non_aktif_tidak_bisa_dipesan(): void
    {
        $this->hotel->update(['status' => HotelStatus::Suspended]);

        $this->expectException(InvalidBookingException::class);

        $this->book();
    }

    /* -----------------------------------------------------------------
     | Anti double booking
     * ----------------------------------------------------------------- */

    public function test_kamar_terakhir_tidak_bisa_dipesan_dua_kali(): void
    {
        $this->roomType->update(['total_rooms' => 1]);

        $pertama = $this->book();

        try {
            $this->book();
            $this->fail('Booking kedua seharusnya ditolak.');
        } catch (RoomNotAvailableException $e) {
            $this->assertStringContainsString('penuh', $e->getMessage());
        }

        // Hanya satu booking yang tersimpan.
        $this->assertSame(1, Booking::withoutGlobalScopes()->count());
        $this->assertSame($pertama->id, Booking::withoutGlobalScopes()->sole()->id);
    }

    public function test_booking_yang_tanggalnya_beririsan_ditolak(): void
    {
        $this->roomType->update(['total_rooms' => 1]);

        $this->book([
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(8)->toDateString(),
        ]);

        // Beririsan sebagian: 6-9 menutup 6,7 yang sudah dipakai 5-8.
        $this->expectException(RoomNotAvailableException::class);
        $this->book([
            'check_in' => now()->addDays(6)->toDateString(),
            'check_out' => now()->addDays(9)->toDateString(),
        ]);
    }

    public function test_booking_yang_tidak_beririsan_tetap_diterima(): void
    {
        $this->roomType->update(['total_rooms' => 1]);

        $this->book([
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
        ]);

        // Mulai tepat pada tanggal check-out sebelumnya => tidak beririsan.
        $kedua = $this->book([
            'check_in' => now()->addDays(7)->toDateString(),
            'check_out' => now()->addDays(9)->toDateString(),
        ]);

        $this->assertSame(BookingStatus::PendingPayment, $kedua->status);
        $this->assertSame(2, Booking::withoutGlobalScopes()->count());
    }

    public function test_sisa_kamar_berkurang_sesuai_jumlah_kamar_yang_dipesan(): void
    {
        // total 3 kamar.
        $this->service->create($this->guest, $this->roomType, $this->payload(['rooms_count' => 2]));

        // Sisa 1 kamar; meminta 2 harus gagal.
        $this->expectException(RoomNotAvailableException::class);
        $this->book(['rooms_count' => 2]);
    }

    public function test_booking_dibatalkan_mengembalikan_stok_kamar(): void
    {
        $this->roomType->update(['total_rooms' => 1]);

        $booking = $this->book();
        $booking->update(['status' => BookingStatus::Cancelled]);

        $kedua = $this->book();

        $this->assertSame(BookingStatus::PendingPayment, $kedua->status);
    }

    public function test_booking_yang_kedaluwarsa_tidak_menahan_stok(): void
    {
        $this->roomType->update(['total_rooms' => 1]);

        $booking = $this->book();
        $booking->update(['status' => BookingStatus::Expired]);

        $this->assertNotNull($this->book());
    }

    /* -----------------------------------------------------------------
     | Mekanisme penguncian
     * ----------------------------------------------------------------- */

    public function test_pembuatan_booking_memakai_transaksi_dan_lock_for_update(): void
    {
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        // Seluruh proses dibungkus transaksi luar milik tes, lalu dibatalkan.
        $levelAwal = DB::transactionLevel();

        DB::beginTransaction();
        $this->service->create($this->guest, $this->roomType, $this->payload());
        DB::rollBack();

        // Baris tipe kamar harus dikunci sebelum ketersediaan dihitung ulang.
        $lockQuery = collect($queries)->first(
            fn (string $sql) => str_contains($sql, 'room_types') && str_contains(strtolower($sql), 'for update')
        );

        $this->assertNotNull($lockQuery, 'Query lockForUpdate pada room_types tidak ditemukan.');
        // Rollback service tidak boleh meninggalkan transaksi menggantung.
        $this->assertSame($levelAwal, DB::transactionLevel());
    }

    public function test_ketersediaan_dihitung_ulang_di_dalam_transaksi(): void
    {
        $this->roomType->update(['total_rooms' => 1]);

        // Booking "luar" yang dibuat tepat sebelum pemanggilan service.
        $this->book();

        try {
            $this->book();
            $this->fail('Booking kedua seharusnya ditolak.');
        } catch (RoomNotAvailableException) {
            // Rollback harus bersih: tidak ada baris tambahan.
            $this->assertSame(1, Booking::withoutGlobalScopes()->count());
        }
    }

    public function test_owner_sebagai_tamu_tetap_bisa_booking_hotel_milik_orang_lain(): void
    {
        $hotelLain = Hotel::factory()->create();
        $tipeLain = RoomType::factory()->create([
            'hotel_id' => $hotelLain->id,
            'is_active' => true,
            'capacity' => 2,
        ]);

        $booking = $this->service->create($this->owner, $tipeLain, $this->payload());

        $this->assertSame($hotelLain->id, $booking->hotel_id);
    }
}
