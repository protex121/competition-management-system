<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\Registration;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    protected $model = Submission::class;

    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'title' => ucfirst(fake()->unique()->words(3, true)),
            'description' => fake()->optional()->paragraph(),
            'project_url' => null,
            'file_path' => null,
            'file_original_name' => null,
            'status' => SubmissionStatus::Draft,
            'submitted_at' => null,
        ];
    }

    public function finalized(): static
    {
        return $this->state(fn (): array => [
            'status' => SubmissionStatus::Finalized,
            'submitted_at' => now(),
        ]);
    }
}
