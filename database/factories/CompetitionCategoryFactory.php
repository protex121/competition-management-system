<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CategoryStatus;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CompetitionCategory>
 */
class CompetitionCategoryFactory extends Factory
{
    protected $model = CompetitionCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'competition_id' => Competition::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => null,
            'status' => CategoryStatus::Draft,
            'sort_order' => 0,
            'max_participants' => null,
            'registration_ends_at' => null,
            'is_default' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => CategoryStatus::Active,
        ]);
    }

    public function defaultGeneral(): static
    {
        return $this->state(fn (): array => [
            'name' => 'General',
            'slug' => 'general',
            'status' => CategoryStatus::Draft,
            'is_default' => true,
            'sort_order' => 0,
        ]);
    }
}
