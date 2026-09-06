<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\RubricCriterion;
use App\Models\Score;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Score>
 */
class ScoreFactory extends Factory
{
    protected $model = Score::class;

    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'rubric_criterion_id' => RubricCriterion::factory(),
            'judge_id' => User::factory()->state(['role' => UserRole::Judge]),
            'score' => fake()->numberBetween(0, 10),
            'comment' => null,
        ];
    }
}
