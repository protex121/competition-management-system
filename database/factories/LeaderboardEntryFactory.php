<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CompetitionCategory;
use App\Models\LeaderboardEntry;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaderboardEntry>
 */
class LeaderboardEntryFactory extends Factory
{
    protected $model = LeaderboardEntry::class;

    public function definition(): array
    {
        return [
            'competition_category_id' => CompetitionCategory::factory(),
            'submission_id' => Submission::factory(),
            'aggregate_score' => fake()->randomFloat(2, 0, 100),
            'judge_count' => fake()->numberBetween(1, 3),
            'rank' => fake()->numberBetween(1, 10),
        ];
    }
}
