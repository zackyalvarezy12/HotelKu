<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\HotelPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HotelPhoto>
 */
class HotelPhotoFactory extends Factory
{
    protected $model = HotelPhoto::class;

    public function definition(): array
    {
        return [
            'hotel_id' => Hotel::factory(),
            'path' => 'hotels/contoh-'.fake()->unique()->numerify('####').'.jpg',
            'is_cover' => false,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    public function cover(): static
    {
        return $this->state(fn () => ['is_cover' => true]);
    }
}
