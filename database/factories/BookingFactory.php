<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        // Tanggal menginap acak di masa depan.
        $checkIn = Carbon::now()->addDays(fake()->numberBetween(1, 30))->startOfDay();

        return [
            'code' => 'BK-'.now()->format('ymd').'-'.strtoupper(Str::random(6)),
            'user_id' => User::factory()->guest(),
            // room_type harus berada pada hotel yang sama dengan hotel_id.
            'room_type_id' => fn (array $attributes) => RoomType::factory()
                ->create(['hotel_id' => $attributes['hotel_id']])
                ->getKey(),
            'hotel_id' => Hotel::factory(),
            'room_id' => null,
            'guest_name' => fake()->name(),
            'guest_phone' => '08'.fake()->numerify('##########'),
            'check_in' => $checkIn,
            'check_out' => fn (array $attributes) => Carbon::parse($attributes['check_in'])->addDays(2),
            'nights' => fn (array $attributes) => (int) Carbon::parse($attributes['check_in'])
                ->diffInDays(Carbon::parse($attributes['check_out'])),
            'guests' => 2,
            'rooms_count' => 1,
            // Harga selalu dihitung dari harga kamar, bukan nilai acak.
            'subtotal' => fn (array $attributes) => (float) RoomType::withoutGlobalScopes()
                ->find($attributes['room_type_id'])->base_price * $attributes['nights'] * $attributes['rooms_count'],
            'discount_amount' => 0,
            'total_price' => fn (array $attributes) => (float) $attributes['subtotal'] - (float) $attributes['discount_amount'],
            'promotion_id' => null,
            'voucher_code' => null,
            'special_request' => null,
            'status' => BookingStatus::PendingPayment,
            'expires_at' => now()->addDay(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => [
            'status' => BookingStatus::Confirmed,
            'expires_at' => null,
            'confirmed_at' => now(),
        ]);
    }

    public function checkedIn(): static
    {
        return $this->state(fn () => [
            'status' => BookingStatus::CheckedIn,
            'expires_at' => null,
            'confirmed_at' => now()->subDay(),
            'checked_in_at' => now(),
            'check_in' => now()->subDay()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => BookingStatus::Completed,
            'expires_at' => null,
            'confirmed_at' => now()->subDays(5),
            'checked_in_at' => now()->subDays(4),
            'checked_out_at' => now()->subDays(2),
            'check_in' => now()->subDays(4)->toDateString(),
            'check_out' => now()->subDays(2)->toDateString(),
        ]);
    }
}
