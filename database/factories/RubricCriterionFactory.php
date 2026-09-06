<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Rubric;
use App\Models\RubricCriterion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RubricCriterion>
 */
class RubricCriterionFactory extends Factory
{
    protected $model = RubricCriterion::class;

    public function definition(): array
    {
        return [
            'rubric_id' => Rubric::factory(),
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'description' => null,
            'max_score' => 10,
            'sort_order' => 0,
        ];
    }
}
