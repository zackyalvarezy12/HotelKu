<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Livewire\Guest\BookingReview;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingReviewTest extends TestCase
{
    use RefreshDatabase;

    private Hotel $hotel;

    private RoomType $roomType;

    private User $guest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->guest = User::factory()->guest()->create();
        $this->hotel = Hotel::factory()->create();
        $this->roomType = RoomType::factory()->create(['hotel_id' => $this->hotel->id]);
    }

    private function booking(BookingStatus $status = BookingStatus::Completed, array $overrides = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'user_id' => $this->guest->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'status' => $status,
        ], $overrides));
    }

    public function test_guest_dapat_mengirim_ulasan_untuk_booking_selesai(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->guest);
        Livewire::test(BookingReview::class, ['booking' => $booking->id])
            ->set('rating', 4)
            ->set('comment', 'Kamar bersih dan pelayanan ramah.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('bookings.index'));

        $this->assertDatabaseHas('reviews', [
            'booking_id' => $booking->id,
            'user_id' => $this->guest->id,
            'hotel_id' => $this->hotel->id,
            'rating' => 4,
            'comment' => 'Kamar bersih dan pelayanan ramah.',
        ]);

        $this->get(route('hotels.show', $this->hotel->slug))
            ->assertSuccessful()
            ->assertSee('Ulasan Tamu')
            ->assertSee('Kamar bersih dan pelayanan ramah.')
            ->assertSee('4.0');
    }

    public function test_ulasan_hanya_bisa_dibuat_setelah_booking_selesai(): void
    {
        $booking = $this->booking(BookingStatus::Confirmed);

        $this->actingAs($this->guest)
            ->get(route('bookings.review', $booking->id))
            ->assertNotFound();
    }

    public function test_guest_tidak_dapat_mengulas_booking_tamu_lain(): void
    {
        $booking = $this->booking();
        $otherGuest = User::factory()->guest()->create();

        $this->actingAs($otherGuest)
            ->get(route('bookings.review', $booking->id))
            ->assertNotFound();
    }
}