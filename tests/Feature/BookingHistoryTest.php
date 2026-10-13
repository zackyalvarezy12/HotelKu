<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Halaman riwayat booking: isolasi data per tamu dan filter status. */
class BookingHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;

    protected RoomType $roomType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->hotel = Hotel::factory()->create(['name' => 'Hotel Riwayat Uji']);
        $this->roomType = RoomType::factory()->create(['hotel_id' => $this->hotel->id]);
    }

    private function booking(User $guest, BookingStatus $status = BookingStatus::Confirmed, array $overrides = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'user_id' => $guest->id,
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
            'status' => $status,
        ], $overrides));
    }

    public function test_tamu_belum_login_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/bookings')->assertRedirect('/login');
    }

    public function test_tamu_melihat_daftar_booking_miliknya(): void
    {
        $guest = User::factory()->guest()->create();
        $lain = User::factory()->guest()->create();

        $milikSaya = $this->booking($guest);
        $this->booking($lain);

        $response = $this->actingAs($guest)->get('/bookings');

        $response->assertSuccessful()
            ->assertSee('Riwayat Booking')
            ->assertSee('Hotel Riwayat Uji')
            ->assertSee($milikSaya->code)
            ->assertDontSee(Booking::withoutGlobalScopes()->where('user_id', $lain->id)->sole()->code);
    }

    public function test_tamu_tidak_melihat_booking_tamu_lain(): void
    {
        $guest = User::factory()->guest()->create();
        $lain = User::factory()->guest()->create();

        $this->booking($lain, overrides: ['code' => 'BK-LAIN-000001']);

        $this->actingAs($guest)
            ->get('/bookings')
            ->assertSuccessful()
            ->assertDontSee('BK-LAIN-000001');
    }

    public function test_halaman_kosong_menampilkan_ajakan_mencari_hotel(): void
    {
        $guest = User::factory()->guest()->create();

        $this->actingAs($guest)
            ->get('/bookings')
            ->assertSuccessful()
            ->assertSee('Belum ada booking');
    }

    public function test_filter_status_menyaring_daftar(): void
    {
        $guest = User::factory()->guest()->create();

        $this->booking($guest, BookingStatus::Confirmed, ['code' => 'BK-CONF-000001']);
        $this->booking($guest, BookingStatus::Expired, ['code' => 'BK-EXPI-000001']);

        $this->actingAs($guest)
            ->get('/bookings?status=confirmed')
            ->assertSuccessful()
            ->assertSee('BK-CONF-000001')
            ->assertDontSee('BK-EXPI-000001');

        $this->actingAs($guest)
            ->get('/bookings?status=expired')
            ->assertSuccessful()
            ->assertSee('BK-EXPI-000001')
            ->assertDontSee('BK-CONF-000001');
    }

    public function test_status_tidak_dikenal_diabaikan(): void
    {
        $guest = User::factory()->guest()->create();
        $this->booking($guest, BookingStatus::Confirmed, ['code' => 'BK-CONF-000002']);

        $this->actingAs($guest)
            ->get('/bookings?status=ngawur')
            ->assertSuccessful()
            ->assertSee('BK-CONF-000002');
    }

    public function test_booking_pending_menampilkan_batas_waktu_pembayaran(): void
    {
        $guest = User::factory()->guest()->create();

        $this->booking($guest, BookingStatus::PendingPayment, [
            'expires_at' => now()->addHours(10),
        ]);

        $this->actingAs($guest)
            ->get('/bookings')
            ->assertSuccessful()
            ->assertSee('Selesaikan pembayaran sebelum');
    }

    public function test_booking_pending_yang_lewat_batas_menampilkan_peringatan(): void
    {
        $guest = User::factory()->guest()->create();

        $this->booking($guest, BookingStatus::PendingPayment, [
            'expires_at' => now()->subHour(),
        ]);

        $this->actingAs($guest)
            ->get('/bookings')
            ->assertSuccessful()
            ->assertSee('Batas waktu pembayaran sudah lewat');
    }

    /**
     * Regresi: global scope ScopedToManagedHotels hanya untuk panel hotel.
     * Owner yang memesan sebagai tamu harus tetap melihat booking pribadinya.
     */
    public function test_owner_melihat_booking_pribadinya_di_riwayat(): void
    {
        $owner = User::factory()->owner()->create();

        $booking = $this->booking($owner, BookingStatus::Confirmed, ['code' => 'BK-PRIB-000001']);

        $this->actingAs($owner)
            ->get('/bookings')
            ->assertSuccessful()
            ->assertSee('BK-PRIB-000001');

        $this->assertSame(
            [$booking->id],
            $owner->bookings()->withoutHotelIsolation()->pluck('id')->all()
        );
    }

    public function test_riwayat_menampilkan_total_dan_status(): void
    {
        $guest = User::factory()->guest()->create();

        $this->booking($guest, BookingStatus::PendingPayment, [
            'subtotal' => 1500000,
            'discount_amount' => 150000,
            'total_price' => 1350000,
            'expires_at' => now()->addHours(5),
        ]);

        $this->actingAs($guest)
            ->get('/bookings')
            ->assertSuccessful()
            ->assertSee('Rp1.350.000')
            ->assertSee('Hemat Rp150.000')
            ->assertSee('Menunggu Pembayaran');
    }
}
