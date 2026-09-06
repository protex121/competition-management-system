<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CompetitionJudgeStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\CompetitionJudge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetitionJudge>
 */
class CompetitionJudgeFactory extends Factory
{
    protected $model = CompetitionJudge::class;

    public function definition(): array
    {
        return [
            'competition_id' => Competition::factory(),
            'user_id' => User::factory()->state(['role' => UserRole::Judge]),
            'status' => CompetitionJudgeStatus::Active,
        ];
    }

    public function removed(): static
    {
        return $this->state(fn (): array => [
            'status' => CompetitionJudgeStatus::Removed,
        ]);
    }
}
