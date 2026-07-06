<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CompetitionStatus;
use App\Enums\RegistrationMode;
use App\Models\Competition;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Competition>
 */
class CompetitionFactory extends Factory
{
    protected $model = Competition::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'organization_id' => Organization::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->optional()->paragraph(),
            'status' => CompetitionStatus::Draft,
            'starts_at' => null,
            'ends_at' => null,
            'registration_starts_at' => null,
            'registration_ends_at' => null,
            'max_participants' => null,
            'registration_mode' => RegistrationMode::Individual,
            'min_team_size' => null,
            'max_team_size' => null,
            'requires_coach' => false,
        ];
    }

    public function teamMode(): static
    {
        return $this->state(fn (): array => [
            'registration_mode' => RegistrationMode::Team,
            'min_team_size' => 2,
            'max_team_size' => 5,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => CompetitionStatus::Published,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => CompetitionStatus::Active,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (): array => [
            'status' => CompetitionStatus::Closed,
        ]);
    }
}
