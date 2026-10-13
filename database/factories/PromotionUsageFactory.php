<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Promotion;
use App\Models\PromotionUsage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PromotionUsage>
 */
class PromotionUsageFactory extends Factory
{
    protected $model = PromotionUsage::class;

    public function definition(): array
    {
        return [
            'promotion_id' => Promotion::factory(),
            'booking_id' => Booking::factory(),
            'user_id' => fn (array $attributes) => Booking::withoutGlobalScopes()
                ->find($attributes['booking_id'])->user_id,
            'discount_amount' => 100000,
            'used_at' => now(),
        ];
    }
}
