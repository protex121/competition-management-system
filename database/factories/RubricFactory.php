<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Competition;
use App\Models\Rubric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rubric>
 */
class RubricFactory extends Factory
{
    protected $model = Rubric::class;

    public function definition(): array
    {
        return [
            'competition_id' => Competition::factory(),
        ];
    }
}
