<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'amount' => fn (array $attributes) => Booking::withoutGlobalScopes()
                ->find($attributes['booking_id'])?->total_price ?? 0,
            'proof_path' => null,
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
            'gateway_ref' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
