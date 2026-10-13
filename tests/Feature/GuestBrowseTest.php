<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\HotelStatus;
use App\Livewire\Guest\HotelSearch;
use App\Livewire\Guest\SearchForm;
use App\Models\Booking;
use App\Models\Facility;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Halaman tamu: hanya hotel aktif yang tampil, filter, dan detail hotel. */
class GuestBrowseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    /** Membuat hotel beserta satu tipe kamar aktif. */
    private function makeHotel(array $hotelAttributes = [], array $roomAttributes = []): Hotel
    {
        $owner = User::factory()->owner()->create();
        $hotel = Hotel::factory()->create($hotelAttributes + ['owner_id' => $owner->id]);

        RoomType::factory()->create($roomAttributes + [
            'hotel_id' => $hotel->id,
            'is_active' => true,
            'base_price' => 500000,
            'capacity' => 2,
            'total_rooms' => 5,
        ]);

        return $hotel->refresh();
    }

    /* -----------------------------------------------------------------
     | Beranda
     * ----------------------------------------------------------------- */

    public function test_beranda_dapat_dibuka_dan_menampilkan_hotel_aktif_saja(): void
    {
        $this->makeHotel(['name' => 'Hotel Aktif Uji', 'city' => 'Jakarta']);
        $this->makeHotel(['name' => 'Hotel Pending Uji', 'status' => HotelStatus::Pending]);
        $this->makeHotel(['name' => 'Hotel Suspended Uji', 'status' => HotelStatus::Suspended]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('Hotel Aktif Uji')
            ->assertDontSee('Hotel Pending Uji')
            ->assertDontSee('Hotel Suspended Uji');
    }

    /* -----------------------------------------------------------------
     | Hasil pencarian
     * ----------------------------------------------------------------- */

    public function test_halaman_hasil_pencarian_hanya_menampilkan_hotel_aktif(): void
    {
        $this->makeHotel(['name' => 'Hotel Aktif Uji', 'city' => 'Jakarta']);
        $this->makeHotel(['name' => 'Hotel Pending Uji', 'status' => HotelStatus::Pending]);
        $this->makeHotel(['name' => 'Hotel Suspended Uji', 'status' => HotelStatus::Suspended]);

        $this->get('/hotels')
            ->assertSuccessful()
            ->assertSee('Hotel Aktif Uji')
            ->assertDontSee('Hotel Pending Uji')
            ->assertDontSee('Hotel Suspended Uji');
    }

    public function test_hotel_tanpa_tipe_kamar_aktif_tidak_muncul(): void
    {
        $hotel = $this->makeHotel(['name' => 'Hotel Tanpa Kamar Uji']);
        $hotel->roomTypes()->update(['is_active' => false]);

        $this->get('/hotels')->assertSuccessful()->assertDontSee('Hotel Tanpa Kamar Uji');
    }

    public function test_filter_kota_berjalan_lewat_url(): void
    {
        $this->makeHotel(['name' => 'Hotel Jakarta Uji', 'city' => 'Jakarta']);
        $this->makeHotel(['name' => 'Hotel Bandung Uji', 'city' => 'Bandung']);

        $this->get('/hotels?kota=Jakarta')
            ->assertSuccessful()
            ->assertSee('Hotel Jakarta Uji')
            ->assertDontSee('Hotel Bandung Uji');
    }

    public function test_filter_bintang(): void
    {
        $this->makeHotel(['name' => 'Hotel Bintang Lima Uji', 'star_rating' => 5]);
        $this->makeHotel(['name' => 'Hotel Bintang Dua Uji', 'star_rating' => 2]);

        Livewire::test(HotelSearch::class)
            ->set('stars', [5])
            ->assertSee('Hotel Bintang Lima Uji')
            ->assertDontSee('Hotel Bintang Dua Uji');
    }

    public function test_filter_fasilitas_mengharuskan_semua_fasilitas_terpilih(): void
    {
        $wifi = Facility::factory()->create(['name' => 'WiFi Uji']);
        $pool = Facility::factory()->create(['name' => 'Kolam Uji']);

        $lengkap = $this->makeHotel(['name' => 'Hotel Fasilitas Lengkap Uji']);
        $lengkap->facilities()->sync([$wifi->id, $pool->id]);

        $sebagian = $this->makeHotel(['name' => 'Hotel Fasilitas Sebagian Uji']);
        $sebagian->facilities()->sync([$wifi->id]);

        Livewire::test(HotelSearch::class)
            ->set('facilities', [$wifi->id, $pool->id])
            ->assertSee('Hotel Fasilitas Lengkap Uji')
            ->assertDontSee('Hotel Fasilitas Sebagian Uji');
    }

    public function test_filter_rentang_harga(): void
    {
        $this->makeHotel(['name' => 'Hotel Murah Uji'], ['base_price' => 300000]);
        $this->makeHotel(['name' => 'Hotel Mahal Uji'], ['base_price' => 2000000]);

        Livewire::test(HotelSearch::class)
            ->set('maxPrice', '500000')
            ->assertSee('Hotel Murah Uji')
            ->assertDontSee('Hotel Mahal Uji');
    }

    public function test_filter_tanggal_menyembunyikan_hotel_yang_kamarnya_penuh(): void
    {
        $hotel = $this->makeHotel(['name' => 'Hotel Penuh Uji'], ['total_rooms' => 1]);
        $roomType = $hotel->roomTypes()->first();
        $guest = User::factory()->guest()->create();

        Booking::factory()->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomType->id,
            'user_id' => $guest->id,
            'check_in' => '2026-11-10',
            'check_out' => '2026-11-12',
            'rooms_count' => 1,
            'status' => BookingStatus::Confirmed,
        ]);

        // Rentang yang beririsan => hotel harus hilang.
        Livewire::test(HotelSearch::class)
            ->set('checkIn', '2026-11-11')
            ->set('checkOut', '2026-11-13')
            ->assertDontSee('Hotel Penuh Uji');

        // Rentang yang tidak beririsan => hotel muncul kembali.
        Livewire::test(HotelSearch::class)
            ->set('checkIn', '2026-11-20')
            ->set('checkOut', '2026-11-22')
            ->assertSee('Hotel Penuh Uji');
    }

    public function test_filter_jumlah_tamu_menyaring_kapasitas_kamar(): void
    {
        $this->makeHotel(['name' => 'Hotel Kapasitas Dua Uji'], ['capacity' => 2]);

        Livewire::test(HotelSearch::class)
            ->set('guests', 2)
            ->assertSee('Hotel Kapasitas Dua Uji');

        Livewire::test(HotelSearch::class)
            ->set('guests', 5)
            ->assertDontSee('Hotel Kapasitas Dua Uji');
    }

    public function test_hapus_semua_filter(): void
    {
        $this->makeHotel(['name' => 'Hotel Jakarta Uji', 'city' => 'Jakarta']);

        Livewire::test(HotelSearch::class)
            ->set('city', 'Jakarta')
            ->set('stars', [5])
            ->call('resetFilters')
            ->assertSet('city', '')
            ->assertSet('stars', []);
    }

    /**
     * Regresi: global scope ScopedToManagedHotels hanya untuk panel.
     * Owner yang menjelajah halaman publik harus tetap melihat hotel owner lain.
     */
    public function test_owner_tetap_melihat_semua_hotel_aktif_di_halaman_publik(): void
    {
        $ownerA = User::factory()->owner()->create();
        $ownerB = User::factory()->owner()->create();

        $hotelA = Hotel::factory()->create(['owner_id' => $ownerA->id, 'name' => 'Hotel Owner A']);
        $hotelB = Hotel::factory()->create(['owner_id' => $ownerB->id, 'name' => 'Hotel Owner B']);
        foreach ([$hotelA, $hotelB] as $hotel) {
            RoomType::factory()->create(['hotel_id' => $hotel->id, 'is_active' => true]);
        }

        $this->actingAs($ownerA)
            ->get('/hotels')
            ->assertSuccessful()
            ->assertSee('Hotel Owner A')
            ->assertSee('Hotel Owner B');

        $this->actingAs($ownerA)
            ->get(route('hotels.show', $hotelB->slug))
            ->assertSuccessful();
    }

    /* -----------------------------------------------------------------
     | Detail hotel
     * ----------------------------------------------------------------- */

    public function test_detail_hotel_menampilkan_galeri_fasilitas_tipe_kamar_dan_peta(): void
    {
        $facility = Facility::factory()->create(['name' => 'Kolam Renang Uji']);
        $hotel = $this->makeHotel(
            ['name' => 'Hotel Detail Uji', 'latitude' => -6.2088, 'longitude' => 106.8456, 'description' => 'Deskripsi uji hotel.'],
            ['name' => 'Deluxe Uji', 'base_price' => 750000]
        );
        $hotel->facilities()->sync([$facility->id]);

        $this->get(route('hotels.show', $hotel->slug))
            ->assertSuccessful()
            ->assertSee('Hotel Detail Uji')
            ->assertSee('Deskripsi uji hotel.')
            ->assertSee('Kolam Renang Uji')
            ->assertSee('Deluxe Uji')
            ->assertSee('Rp750.000')
            ->assertSee('data-hotel-map', escape: false)
            // Bundel Leaflet harus ikut dimuat di halaman detail.
            ->assertSee('build/assets/map', escape: false)
            ->assertSee('-6.2088');
    }

    public function test_detail_hotel_menampilkan_sisa_kamar_saat_tanggal_dipilih(): void
    {
        $hotel = $this->makeHotel(['name' => 'Hotel Sisa Kamar Uji'], ['name' => 'Standar Uji', 'total_rooms' => 3]);
        $roomType = $hotel->roomTypes()->first();
        $guest = User::factory()->guest()->create();

        Booking::factory()->create([
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomType->id,
            'user_id' => $guest->id,
            'check_in' => '2026-12-01',
            'check_out' => '2026-12-03',
            'rooms_count' => 1,
            'status' => BookingStatus::Confirmed,
        ]);

        $this->get(route('hotels.show', $hotel->slug).'?masuk=2026-12-01&keluar=2026-12-03&tamu=2')
            ->assertSuccessful()
            ->assertSee('Tersedia 2 kamar');
    }

    public function test_detail_hotel_non_aktif_menghasilkan_404(): void
    {
        $pending = $this->makeHotel(['name' => 'Hotel Pending Detail Uji', 'status' => HotelStatus::Pending]);
        $suspended = $this->makeHotel(['name' => 'Hotel Suspended Detail Uji', 'status' => HotelStatus::Suspended]);

        $this->get(route('hotels.show', $pending->slug))->assertNotFound();
        $this->get(route('hotels.show', $suspended->slug))->assertNotFound();
    }

    public function test_detail_hotel_tidak_menampilkan_tipe_kamar_non_aktif(): void
    {
        $hotel = $this->makeHotel(['name' => 'Hotel Kamar Nonaktif Uji'], ['name' => 'Kamar Tampil Uji']);
        RoomType::factory()->create([
            'hotel_id' => $hotel->id,
            'name' => 'Kamar Tersembunyi Uji',
            'is_active' => false,
        ]);

        $this->get(route('hotels.show', $hotel->slug))
            ->assertSuccessful()
            ->assertSee('Kamar Tampil Uji')
            ->assertDontSee('Kamar Tersembunyi Uji');
    }

    /* -----------------------------------------------------------------
     | Form pencarian
     * ----------------------------------------------------------------- */

    public function test_form_pencarian_mengarahkan_ke_halaman_hasil(): void
    {
        Livewire::test(SearchForm::class)
            ->set('city', 'Jakarta')
            ->set('checkIn', now()->addDay()->toDateString())
            ->set('checkOut', now()->addDays(3)->toDateString())
            ->set('guests', 3)
            ->call('search')
            ->assertRedirect(route('hotels.index', [
                'kota' => 'Jakarta',
                'masuk' => now()->addDay()->toDateString(),
                'keluar' => now()->addDays(3)->toDateString(),
                'tamu' => 3,
            ]));
    }

    public function test_form_pencarian_menolak_check_out_sebelum_check_in(): void
    {
        Livewire::test(SearchForm::class)
            ->set('checkIn', now()->addDays(5)->toDateString())
            ->set('checkOut', now()->addDays(3)->toDateString())
            ->call('search')
            ->assertHasErrors('checkOut');
    }

    public function test_form_pencarian_menolak_tanggal_check_in_masa_lalu(): void
    {
        Livewire::test(SearchForm::class)
            ->set('checkIn', now()->subDay()->toDateString())
            ->set('checkOut', now()->addDays(2)->toDateString())
            ->call('search')
            ->assertHasErrors('checkIn');
    }
}
