<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\HotelStatus;
use App\Livewire\Guest\BookingForm;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Alur booking dari sisi tamu: halaman pemesanan, validasi, dan hasil akhirnya
 * di halaman riwayat booking.
 */
class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $guest;

    protected Hotel $hotel;

    protected RoomType $roomType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->guest = User::factory()->guest()->create();

        $this->hotel = Hotel::factory()->create(['name' => 'Hotel Pesan Uji']);
        $this->roomType = RoomType::factory()->create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Kamar Uji Deluxe',
            'base_price' => 500000,
            'capacity' => 2,
            'total_rooms' => 3,
            'is_active' => true,
        ]);
    }

    private function url(array $query = []): string
    {
        return '/hotels/'.$this->hotel->slug.'/pesan'.($query ? '?'.http_build_query($query) : '');
    }

    /** Komponen dengan data pemesan terisi; tanggal default 2 malam dari sekarang. */
    private function form()
    {
        return Livewire::test(BookingForm::class, ['hotel' => $this->hotel->slug])
            ->set('roomTypeId', (string) $this->roomType->id)
            ->set('checkIn', now()->addDays(5)->toDateString())
            ->set('checkOut', now()->addDays(7)->toDateString())
            ->set('guests', 2)
            ->set('rooms', 1)
            ->set('guestName', 'Tamu Pesan Uji')
            ->set('guestPhone', '081200000000');
    }

    /* -----------------------------------------------------------------
     | Akses halaman
     * ----------------------------------------------------------------- */

    public function test_halaman_pemesanan_butuh_login(): void
    {
        $this->get($this->url())->assertRedirect('/login');
    }

    public function test_halaman_pemesanan_menampilkan_tipe_kamar_harga_dan_batas_bayar(): void
    {
        $this->actingAs($this->guest)
            ->get($this->url(['kamar' => $this->roomType->id]))
            ->assertSuccessful()
            ->assertSee('Pemesanan Kamar')
            ->assertSee('Hotel Pesan Uji')
            ->assertSee('Kamar Uji Deluxe')
            ->assertSee('500.000')
            ->assertSee('Pesan Sekarang')
            ->assertSee('menunggu pembayaran');
    }

    public function test_tautan_detail_hotel_membawa_tanggal_jumlah_tamu_dan_total(): void
    {
        // Konteks dari tautan "Pesan" di halaman detail hotel.
        $this->actingAs($this->guest)
            ->get($this->url([
                'kamar' => $this->roomType->id,
                'masuk' => now()->addDays(5)->toDateString(),
                'keluar' => now()->addDays(7)->toDateString(),
                'tamu' => 2,
            ]))
            ->assertSuccessful()
            ->assertSee('1.000.000'); // 2 malam x Rp500.000
    }

    public function test_detail_hotel_memiliki_tautan_pesan_untuk_tipe_kamar(): void
    {
        $this->get('/hotels/'.$this->hotel->slug)
            ->assertSuccessful()
            ->assertSee('/hotels/'.$this->hotel->slug.'/pesan', escape: false);
    }

    public function test_hotel_belum_terverifikasi_tidak_bisa_dipesan(): void
    {
        $pending = Hotel::factory()->pending()->create();

        $this->actingAs($this->guest)
            ->get('/hotels/'.$pending->slug.'/pesan')
            ->assertNotFound();
    }

    public function test_slug_hotel_tidak_dikenal_menghasilkan_404(): void
    {
        $this->actingAs($this->guest)
            ->get('/hotels/hotel-yang-tidak-ada/pesan')
            ->assertNotFound();
    }

    /* -----------------------------------------------------------------
     | Pemesanan berhasil
     * ----------------------------------------------------------------- */

    public function test_tamu_berhasil_membuat_booking_dan_diarahkan_ke_riwayat(): void
    {
        $this->actingAs($this->guest);

        $this->form()
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('bookings.index'));

        $booking = Booking::withoutGlobalScopes()->sole();

        $this->assertSame(BookingStatus::PendingPayment, $booking->status);
        $this->assertSame($this->guest->id, $booking->user_id);
        $this->assertSame($this->hotel->id, $booking->hotel_id);
        $this->assertSame($this->roomType->id, $booking->room_type_id);
        $this->assertSame(2, $booking->nights);
        $this->assertSame('1000000.00', (string) $booking->subtotal);
        $this->assertSame('1000000.00', (string) $booking->total_price);
        $this->assertSame('Tamu Pesan Uji', $booking->guest_name);
        $this->assertNotNull($booking->expires_at);
    }

    public function test_booking_baru_langsung_tampil_di_halaman_riwayat(): void
    {
        $this->actingAs($this->guest);

        $this->form()->call('submit')->assertRedirect(route('bookings.index'));

        $booking = Booking::withoutGlobalScopes()->sole();

        $this->actingAs($this->guest)
            ->get('/bookings')
            ->assertSuccessful()
            ->assertSee($booking->code)
            ->assertSee('Hotel Pesan Uji')
            ->assertSee('Menunggu Pembayaran');
    }

    public function test_total_di_halaman_dihitung_ulang_saat_jumlah_kamar_berubah(): void
    {
        $this->actingAs($this->guest);

        $this->form()
            ->set('rooms', 2)
            ->assertSee('2.000.000')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame('2000000.00', (string) Booking::withoutGlobalScopes()->sole()->subtotal);
    }

    /* -----------------------------------------------------------------
     | Validasi
     * ----------------------------------------------------------------- */

    public function test_check_out_harus_setelah_check_in(): void
    {
        $this->actingAs($this->guest);

        $this->form()
            ->set('checkOut', now()->addDays(5)->toDateString())
            ->call('submit')
            ->assertHasErrors(['checkOut'])
            ->assertNoRedirect();

        $this->assertSame(0, Booking::withoutGlobalScopes()->count());
    }

    public function test_check_in_masa_lalu_ditolak(): void
    {
        $this->actingAs($this->guest);

        $this->form()
            ->set('checkIn', now()->subDay()->toDateString())
            ->set('checkOut', now()->addDay()->toDateString())
            ->call('submit')
            ->assertHasErrors(['checkIn']);

        $this->assertSame(0, Booking::withoutGlobalScopes()->count());
    }

    public function test_tipe_kamar_wajib_dipilih(): void
    {
        $this->actingAs($this->guest);

        $this->form()
            ->set('roomTypeId', '')
            ->call('submit')
            ->assertHasErrors(['roomTypeId']);

        $this->assertSame(0, Booking::withoutGlobalScopes()->count());
    }

    public function test_data_pemesan_wajib_diisi(): void
    {
        $this->actingAs($this->guest);

        $this->form()
            ->set('guestName', '')
            ->set('guestPhone', 'abc')
            ->call('submit')
            ->assertHasErrors(['guestName', 'guestPhone']);

        $this->assertSame(0, Booking::withoutGlobalScopes()->count());
    }

    public function test_tamu_tidak_bisa_melebihi_kapasitas_kamar(): void
    {
        $this->actingAs($this->guest);

        // kapasitas 2 x 1 kamar = 2 orang.
        $this->form()
            ->set('guests', 4)
            ->call('submit')
            ->assertSet('bookingError', fn (?string $message) => str_contains((string) $message, 'kapasitas'));

        $this->assertSame(0, Booking::withoutGlobalScopes()->count());
    }

    /* -----------------------------------------------------------------
     | Anti double booking lewat antarmuka
     * ----------------------------------------------------------------- */

    public function test_kamar_penuh_menampilkan_pesan_bukan_halaman_error(): void
    {
        $this->roomType->update(['total_rooms' => 1]);

        // Kamar terakhir dipesan lebih dulu.
        $this->actingAs($this->guest);
        $this->form()->call('submit')->assertHasNoErrors();

        $this->assertSame(1, Booking::withoutGlobalScopes()->count());

        // Percobaan kedua harus gagal dengan pesan yang ramah, bukan halaman error.
        $this->form()
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('bookingError', fn (?string $message) => str_contains((string) $message, 'penuh'))
            ->assertSee('penuh')
            // Pesan hilang begitu tanggal diubah (hook updated()).
            ->set('checkIn', now()->addDays(20)->toDateString())
            ->set('checkOut', now()->addDays(22)->toDateString())
            ->assertSet('bookingError', null);

        $this->assertSame(1, Booking::withoutGlobalScopes()->count());
    }

    public function test_tipe_kamar_milik_hotel_lain_tidak_bisa_dipesan(): void
    {
        $hotelLain = Hotel::factory()->create();
        $tipeLain = RoomType::factory()->create(['hotel_id' => $hotelLain->id]);

        $this->actingAs($this->guest);

        $this->form()
            ->set('roomTypeId', (string) $tipeLain->id)
            ->call('submit')
            ->assertHasErrors(['roomTypeId']);

        $this->assertSame(0, Booking::withoutGlobalScopes()->count());
    }

    public function test_stok_berkurang_setelah_pemesanan_berhasil(): void
    {
        $this->actingAs($this->guest);

        $this->form()
            ->set('rooms', 2)
            ->call('submit')
            ->assertHasNoErrors();

        // Sisa 1 kamar; meminta 2 kamar berikutnya harus ditolak.
        $this->form()
            ->set('rooms', 2)
            ->call('submit')
            ->assertSet('bookingError', fn (?string $message) => str_contains((string) $message, 'tersisa'));

        $this->assertSame(1, Booking::withoutGlobalScopes()->count());
    }

    /** Hotel dinonaktifkan SELAGI form terbuka (bukan saat halaman dibuka). */
    public function test_hotel_yang_diubah_jadi_suspended_saat_form_terbuka_tidak_bisa_dipesan(): void
    {
        $this->actingAs($this->guest);

        $komponen = $this->form(); // hotel masih aktif ketika form dibuka

        $this->hotel->update(['status' => HotelStatus::Suspended]);

        $komponen
            ->call('submit')
            ->assertSet('bookingError', fn (?string $message) => str_contains((string) $message, 'tidak menerima pemesanan'))
            // Halaman tetap tampil rapi, bukan 404/500.
            ->assertSee('tidak menerima pemesanan');

        $this->assertSame(0, Booking::withoutGlobalScopes()->count());
    }
}
