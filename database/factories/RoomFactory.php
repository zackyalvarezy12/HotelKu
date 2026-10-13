<?php

namespace Database\Factories;

use App\Enums\RoomStatus;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'room_type_id' => RoomType::factory(),
            'room_number' => (string) fake()->unique()->numberBetween(101, 999),
            'floor' => (string) fake()->numberBetween(1, 8),
            'status' => RoomStatus::Available,
            'notes' => null,
        ];
    }
}
