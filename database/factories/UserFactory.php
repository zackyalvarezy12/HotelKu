<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '08'.fake()->numerify('##########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /** State peran: sekaligus menetapkan role spatie setelah model dibuat. */
    public function role(UserRole $role): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole($role->value));
    }

    public function superAdmin(): static
    {
        return $this->role(UserRole::SuperAdmin);
    }

    public function owner(): static
    {
        return $this->role(UserRole::Owner);
    }

    public function staff(): static
    {
        return $this->role(UserRole::Staff);
    }

    public function guest(): static
    {
        return $this->role(UserRole::Guest);
    }
}
