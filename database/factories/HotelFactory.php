<?php

namespace Database\Factories;

use App\Enums\HotelStatus;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hotel>
 */
class HotelFactory extends Factory
{
    protected $model = Hotel::class;

    protected static array $cities = [
        'Jakarta', 'Bandung', 'Yogyakarta', 'Surabaya', 'Bali',
        'Medan', 'Semarang', 'Makassar', 'Malang', 'Lombok',
    ];

    public function definition(): array
    {
        $name = fake()->company().' Hotel';
        $city = fake()->randomElement(self::$cities);

        return [
            'owner_id' => User::factory()->owner(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'city' => $city,
            'address' => fake()->streetAddress().', '.$city,
            'latitude' => fake()->latitude(-8.5, 1.5),
            'longitude' => fake()->longitude(95, 141),
            'description' => fake()->paragraph(4),
            'phone' => '021'.fake()->numerify('#######'),
            'email' => fake()->unique()->companyEmail(),
            'logo' => null,
            'check_in_time' => '14:00:00',
            'check_out_time' => '12:00:00',
            'star_rating' => fake()->numberBetween(1, 5),
            'status' => HotelStatus::Active,
            'verified_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => HotelStatus::Pending,
            'verified_at' => null,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => HotelStatus::Suspended]);
    }
}
