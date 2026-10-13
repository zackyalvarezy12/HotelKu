<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RoomType>
 */
class RoomTypeFactory extends Factory
{
    protected $model = RoomType::class;

    public function definition(): array
    {
        return [
            'hotel_id' => Hotel::factory(),
            'name' => fake()->randomElement([
                'Standard Room', 'Deluxe Room', 'Superior Room',
                'Family Suite', 'Executive Suite', 'Twin Room',
            ]),
            'description' => fake()->paragraph(3),
            'base_price' => fake()->numberBetween(250, 2500) * 1000,
            'capacity' => fake()->numberBetween(2, 4),
            'bed_type' => fake()->randomElement(['Single Bed', 'Twin Bed', 'Queen Bed', 'King Bed']),
            'size_m2' => fake()->numberBetween(18, 60),
            'total_rooms' => fake()->numberBetween(3, 20),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
