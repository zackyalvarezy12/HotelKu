<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            // Secara default pesan dikirim oleh tamu pemilik percakapan.
            'sender_id' => fn (array $attributes) => Conversation::withoutGlobalScopes()
                ->find($attributes['conversation_id'])->user_id,
            'body' => fake()->sentence(10),
            'attachment' => null,
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn () => ['read_at' => now()]);
    }
}
