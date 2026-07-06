<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ParticipantProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParticipantProfile>
 */
class ParticipantProfileFactory extends Factory
{
    protected $model = ParticipantProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'bio' => fake()->optional()->paragraph(),
            'phone' => fake()->optional()->phoneNumber(),
            'institution' => fake()->optional()->company(),
        ];
    }
}
