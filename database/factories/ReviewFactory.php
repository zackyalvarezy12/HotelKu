<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory()->completed(),
            // user & hotel mengikuti booking agar data konsisten.
            'user_id' => fn (array $attributes) => Booking::withoutGlobalScopes()
                ->find($attributes['booking_id'])->user_id,
            'hotel_id' => fn (array $attributes) => Booking::withoutGlobalScopes()
                ->find($attributes['booking_id'])->hotel_id,
            'rating' => fake()->numberBetween(3, 5),
            'comment' => fake()->sentence(12),
            'reply' => null,
            'replied_at' => null,
        ];
    }
}
