<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Enums\PromotionType;
use App\Models\Hotel;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Promotion>
 */
class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    public function definition(): array
    {
        return [
            'hotel_id' => Hotel::factory(),
            'name' => 'Promo '.fake()->randomElement(['Awal Bulan', 'Liburan', 'Mingguan', 'Spesial']),
            'description' => fake()->sentence(10),
            'banner' => null,
            'type' => PromotionType::AutoDiscount,
            'code' => null,
            'discount_type' => DiscountType::Percent,
            'discount_value' => fake()->randomElement([5, 10, 15, 20]),
            'max_discount' => 500000,
            'min_nights' => 1,
            'min_total' => 0,
            'booking_start' => now()->subMonth()->toDateString(),
            'booking_end' => now()->addMonths(3)->toDateString(),
            'stay_start' => now()->toDateString(),
            'stay_end' => now()->addMonths(6)->toDateString(),
            'valid_days' => null,
            'quota' => null,
            'quota_per_user' => 1,
            'used_count' => 0,
            'is_active' => true,
            'is_public' => true,
        ];
    }

    /** Promo voucher dengan kode unik. */
    public function voucher(?string $code = null): static
    {
        return $this->state(fn () => [
            'type' => PromotionType::Voucher,
            'code' => $code ?? strtoupper(Str::random(8)),
            'discount_type' => DiscountType::Fixed,
            'discount_value' => 100000,
            'quota' => 100,
        ]);
    }
}
