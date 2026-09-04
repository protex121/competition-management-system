<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RegistrationStatus;
use App\Models\CompetitionCategory;
use App\Models\Registration;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    public function definition(): array
    {
        return [
            'competition_category_id' => CompetitionCategory::factory(),
            'user_id' => User::factory(),
            'team_id' => null,
            'status' => RegistrationStatus::Confirmed,
            'withdrawn_at' => null,
        ];
    }

    public function forTeam(): static
    {
        return $this->state(fn (): array => [
            'user_id' => null,
            'team_id' => Team::factory(),
        ]);
    }

    public function withdrawn(): static
    {
        return $this->state(fn (): array => [
            'status' => RegistrationStatus::Withdrawn,
            'withdrawn_at' => now(),
        ]);
    }
}
