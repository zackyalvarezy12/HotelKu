<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;
use App\Services\AvailabilityService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Rumus ketersediaan:
 *   kamar terpakai = SUM(rooms_count) booking berstatus menahan stok
 *                    yang rentangnya beririsan (check_in < keluar AND check_out > masuk)
 *   sisa kamar     = total_rooms - kamar terpakai
 */
class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AvailabilityService $service;

    protected Hotel $hotel;

    protected RoomType $roomType;

    protected User $guest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->service = app(AvailabilityService::class);

        $this->hotel = Hotel::factory()->create();
        $this->roomType = RoomType::factory()->create([
            'hotel_id' => $this->hotel->id,
            'total_rooms' => 5,
        ]);
        $this->guest = User::factory()->guest()->create();
    }

    /** Booking dengan tanggal & status yang ditentukan. */
    private function booking(string $checkIn, string $checkOut, int $rooms = 1, BookingStatus $status = BookingStatus::Confirmed): Booking
    {
        return Booking::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'user_id' => $this->guest->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'rooms_count' => $rooms,
            'status' => $status,
        ]);
    }

    public function test_tanpa_booking_semua_kamar_tersedia(): void
    {
        $this->assertSame(5, $this->service->availableRooms($this->roomType, '2026-11-01', '2026-11-03'));
    }

    public function test_booking_yang_beririsan_mengurangi_ketersediaan(): void
    {
        $this->booking('2026-11-01', '2026-11-03', 2);

        $this->assertSame(3, $this->service->availableRooms($this->roomType, '2026-11-02', '2026-11-04'));
        $this->assertSame(3, $this->service->availableRooms($this->roomType, '2026-10-30', '2026-11-05'));
    }

    public function test_booking_yang_tidak_beririsan_tidak_mengurangi_ketersediaan(): void
    {
        $this->booking('2026-11-01', '2026-11-03');

        // Selesai tepat pada tanggal check-in baru => tidak beririsan.
        $this->assertSame(5, $this->service->availableRooms($this->roomType, '2026-11-03', '2026-11-05'));
        // Mulai tepat pada tanggal check-out baru => tidak beririsan.
        $this->assertSame(5, $this->service->availableRooms($this->roomType, '2026-10-30', '2026-11-01'));
    }

    public function test_hanya_status_menahan_stok_yang_dihitung(): void
    {
        $this->booking('2026-11-01', '2026-11-03', 1, BookingStatus::PendingPayment);
        $this->booking('2026-11-01', '2026-11-03', 1, BookingStatus::Confirmed);
        $this->booking('2026-11-01', '2026-11-03', 1, BookingStatus::CheckedIn);
        $this->booking('2026-11-01', '2026-11-03', 1, BookingStatus::Cancelled);
        $this->booking('2026-11-01', '2026-11-03', 1, BookingStatus::Expired);
        $this->booking('2026-11-01', '2026-11-03', 1, BookingStatus::Completed);

        // Hanya pending_payment + confirmed + checked_in yang menahan stok.
        $this->assertSame(2, $this->service->availableRooms($this->roomType, '2026-11-01', '2026-11-03'));
    }

    public function test_ketersediaan_tidak_pernah_negatif(): void
    {
        $this->booking('2026-11-01', '2026-11-03', 4);
        $this->booking('2026-11-01', '2026-11-03', 4);

        $this->assertSame(0, $this->service->availableRooms($this->roomType, '2026-11-01', '2026-11-03'));
    }

    public function test_availability_for_hotel_menghitung_semua_tipe_kamar(): void
    {
        $kedua = RoomType::factory()->create(['hotel_id' => $this->hotel->id, 'total_rooms' => 3]);
        $this->booking('2026-11-01', '2026-11-03', 2);

        $result = $this->service->availabilityForHotel($this->hotel, '2026-11-01', '2026-11-03');

        $this->assertSame(3, $result[$this->roomType->id]);
        $this->assertSame(3, $result[$kedua->id]);
    }

    public function test_available_hotels_menyaring_hotel_yang_penuh_dan_kapasitas_kurang(): void
    {
        $penuh = Hotel::factory()->create();
        $tipePenuh = RoomType::factory()->create(['hotel_id' => $penuh->id, 'total_rooms' => 1, 'capacity' => 4]);
        $this->booking('2026-11-01', '2026-11-03', 1);
        Booking::factory()->create([
            'hotel_id' => $penuh->id,
            'room_type_id' => $tipePenuh->id,
            'user_id' => $this->guest->id,
            'check_in' => '2026-11-01',
            'check_out' => '2026-11-03',
            'rooms_count' => 1,
            'status' => BookingStatus::Confirmed,
        ]);

        $tersedia = $this->service->availableHotels('2026-11-01', '2026-11-03', 2);

        $this->assertTrue($tersedia->has($this->hotel->id), 'Hotel dengan kamar tersedia harus muncul.');
        $this->assertFalse($tersedia->has($penuh->id), 'Hotel dengan semua kamar terpakai harus tersaring.');
    }

    public function test_available_hotels_menyaring_berdasarkan_jumlah_tamu(): void
    {
        // Tipe kamar kapasitas 2 pada hotel utama.
        $this->roomType->update(['capacity' => 2]);

        $this->assertTrue($this->service->availableHotels('2026-11-01', '2026-11-03', 2)->has($this->hotel->id));
        $this->assertFalse($this->service->availableHotels('2026-11-01', '2026-11-03', 3)->has($this->hotel->id));
    }

    public function test_available_hotels_mengabaikan_tipe_kamar_non_aktif(): void
    {
        $this->roomType->update(['is_active' => false]);

        $this->assertFalse($this->service->availableHotels('2026-11-01', '2026-11-03', 1)->has($this->hotel->id));
    }

    public function test_tanggal_dinormalisasi_dari_objek_carbon(): void
    {
        $this->booking('2026-11-01', '2026-11-03', 1);

        $this->assertSame(4, $this->service->availableRooms(
            $this->roomType,
            Carbon::parse('2026-11-01 15:30:00'),
            Carbon::parse('2026-11-03 09:00:00')
        ));
    }
}
