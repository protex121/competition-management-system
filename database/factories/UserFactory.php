<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'organization_id' => Organization::factory(),
            'role' => UserRole::Participant,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => null,
            'role' => UserRole::SuperAdmin,
        ]);
    }

    public function organizer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Organizer,
        ]);
    }

    public function deactivated(): static
    {
        return $this->state(fn (array $attributes) => [
            'deactivated_at' => now(),
        ]);
    }
}
