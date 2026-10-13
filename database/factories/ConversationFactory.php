<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'hotel_id' => Hotel::factory(),
            'user_id' => User::factory()->guest(),
            'booking_id' => null,
            'last_message_at' => now(),
        ];
    }
}
