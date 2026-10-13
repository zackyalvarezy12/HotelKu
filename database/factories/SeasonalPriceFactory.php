<?php

namespace Database\Factories;

use App\Models\RoomType;
use App\Models\SeasonalPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SeasonalPrice>
 */
class SeasonalPriceFactory extends Factory
{
    protected $model = SeasonalPrice::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 month', '+3 months');

        return [
            'room_type_id' => RoomType::factory(),
            'start_date' => $start,
            'end_date' => (clone $start)->modify('+7 days'),
            'price' => fake()->numberBetween(400, 4000) * 1000,
            'label' => fake()->randomElement(['Musim Liburan', 'Libur Lebaran', 'Natal & Tahun Baru', 'High Season']),
        ];
    }
}
