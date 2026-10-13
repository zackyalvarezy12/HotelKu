<?php

namespace Database\Factories;

use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Facility>
 */
class FacilityFactory extends Factory
{
    protected $model = Facility::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'WiFi', 'AC', 'Kolam Renang', 'Restoran', 'Parkir Gratis',
            'Gym', 'Spa', 'Laundry', 'Resepsionis 24 Jam', 'TV Kabel',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'icon' => 'heroicon-o-sparkles',
            'category' => fake()->randomElement(['umum', 'kamar']),
        ];
    }
}
