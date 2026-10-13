<?php

namespace Database\Factories;

use App\Models\RoomType;
use App\Models\RoomTypePhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RoomTypePhoto>
 */
class RoomTypePhotoFactory extends Factory
{
    protected $model = RoomTypePhoto::class;

    public function definition(): array
    {
        return [
            'room_type_id' => RoomType::factory(),
            'path' => 'room-types/contoh-'.fake()->unique()->numerify('####').'.jpg',
            'sort_order' => fake()->numberBetween(0, 5),
        ];
    }
}
