<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $guest;

    private Hotel $hotel;

    private RoomType $roomType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->guest = User::factory()->guest()->create();
        $this->hotel = Hotel::factory()->create(['name' => 'Hotel Detail Uji']);
        $this->roomType = RoomType::factory()->create(['hotel_id' => $this->hotel->id, 'name' => 'Kamar Detail']);
    }

    public function test_tamu_melihat_detail_booking_dan_tombol_chat_pemilik(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'code' => 'BK-DETAIL-0001',
        ]);

        $this->actingAs($this->guest)
            ->get(route('bookings.show', $booking->id))
            ->assertSuccessful()
            ->assertSee('BK-DETAIL-0001')
            ->assertSee('Hotel Detail Uji')
            ->assertSee('Kamar Detail')
            ->assertSee('Chat Pemilik');
    }

    public function test_tamu_tidak_dapat_membuka_detail_booking_tamu_lain(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
        ]);
        $otherGuest = User::factory()->guest()->create();

        $this->actingAs($otherGuest)
            ->get(route('bookings.show', $booking->id))
            ->assertNotFound();
    }
}