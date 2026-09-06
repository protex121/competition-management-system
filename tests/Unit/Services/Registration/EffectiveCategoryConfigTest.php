<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Registration;

use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Services\Registration\EffectiveCategoryConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EffectiveCategoryConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_inherits_competition_values_when_category_has_no_override(): void
    {
        $deadline = now()->addWeek()->startOfSecond();
        $competition = Competition::factory()->create([
            'max_participants' => 50,
            'registration_ends_at' => $deadline,
        ]);
        $category = CompetitionCategory::factory()->active()->create([
            'competition_id' => $competition->id,
            'max_participants' => null,
            'registration_ends_at' => null,
        ]);

        $config = EffectiveCategoryConfig::for($category);

        $this->assertSame(50, $config->maxParticipants);
        $this->assertTrue($deadline->equalTo($config->registrationEndsAt));
    }

    public function test_category_override_takes_precedence_over_competition(): void
    {
        $competitionDeadline = now()->addWeek()->startOfSecond();
        $categoryDeadline = now()->addDays(2)->startOfSecond();
        $competition = Competition::factory()->create([
            'max_participants' => 50,
            'registration_ends_at' => $competitionDeadline,
        ]);
        $category = CompetitionCategory::factory()->active()->create([
            'competition_id' => $competition->id,
            'max_participants' => 10,
            'registration_ends_at' => $categoryDeadline,
        ]);

        $config = EffectiveCategoryConfig::for($category);

        $this->assertSame(10, $config->maxParticipants);
        $this->assertTrue($categoryDeadline->equalTo($config->registrationEndsAt));
    }

    public function test_is_registration_open_respects_deadline(): void
    {
        $competition = Competition::factory()->create([
            'registration_ends_at' => now()->subDay(),
        ]);
        $category = CompetitionCategory::factory()->active()->create([
            'competition_id' => $competition->id,
        ]);

        $config = EffectiveCategoryConfig::for($category);

        $this->assertFalse($config->isRegistrationOpen(now()));
    }

    public function test_is_registration_open_true_when_no_deadline_set(): void
    {
        $competition = Competition::factory()->create([
            'registration_starts_at' => null,
            'registration_ends_at' => null,
        ]);
        $category = CompetitionCategory::factory()->active()->create([
            'competition_id' => $competition->id,
        ]);

        $config = EffectiveCategoryConfig::for($category);

        $this->assertTrue($config->isRegistrationOpen(now()));
    }

    public function test_has_capacity_respects_max_participants(): void
    {
        $competition = Competition::factory()->create(['max_participants' => 2]);
        $category = CompetitionCategory::factory()->active()->create([
            'competition_id' => $competition->id,
        ]);

        $config = EffectiveCategoryConfig::for($category);

        $this->assertTrue($config->hasCapacity(1));
        $this->assertFalse($config->hasCapacity(2));
    }

    public function test_has_capacity_always_true_when_no_cap_set(): void
    {
        $competition = Competition::factory()->create(['max_participants' => null]);
        $category = CompetitionCategory::factory()->active()->create([
            'competition_id' => $competition->id,
            'max_participants' => null,
        ]);

        $config = EffectiveCategoryConfig::for($category);

        $this->assertTrue($config->hasCapacity(10_000));
    }

    public function test_category_submission_deadline_overrides_competition(): void
    {
        $competitionDeadline = now()->addWeek()->startOfSecond();
        $categoryDeadline = now()->addDays(2)->startOfSecond();
        $competition = Competition::factory()->create([
            'submission_ends_at' => $competitionDeadline,
        ]);
        $category = CompetitionCategory::factory()->active()->create([
            'competition_id' => $competition->id,
            'submission_ends_at' => $categoryDeadline,
        ]);

        $config = EffectiveCategoryConfig::for($category);

        $this->assertTrue($categoryDeadline->equalTo($config->submissionEndsAt));
    }

    public function test_submission_inherits_competition_deadline_when_category_has_no_override(): void
    {
        $deadline = now()->addWeek()->startOfSecond();
        $competition = Competition::factory()->create([
            'submission_ends_at' => $deadline,
        ]);
        $category = CompetitionCategory::factory()->active()->create([
            'competition_id' => $competition->id,
            'submission_ends_at' => null,
        ]);

        $config = EffectiveCategoryConfig::for($category);

        $this->assertTrue($deadline->equalTo($config->submissionEndsAt));
    }

    public function test_is_submission_open_respects_deadline(): void
    {
        $competition = Competition::factory()->create([
            'submission_ends_at' => now()->subDay(),
        ]);
        $category = CompetitionCategory::factory()->active()->create([
            'competition_id' => $competition->id,
        ]);

        $config = EffectiveCategoryConfig::for($category);

        $this->assertFalse($config->isSubmissionOpen(now()));
    }

    public function test_is_submission_open_respects_start_window(): void
    {
        $competition = Competition::factory()->create([
            'submission_starts_at' => now()->addDay(),
        ]);
        $category = CompetitionCategory::factory()->active()->create([
            'competition_id' => $competition->id,
        ]);

        $config = EffectiveCategoryConfig::for($category);

        $this->assertFalse($config->isSubmissionOpen(now()));
    }

    public function test_is_submission_open_true_when_no_window_set(): void
    {
        $competition = Competition::factory()->create([
            'submission_starts_at' => null,
            'submission_ends_at' => null,
        ]);
        $category = CompetitionCategory::factory()->active()->create([
            'competition_id' => $competition->id,
        ]);

        $config = EffectiveCategoryConfig::for($category);

        $this->assertTrue($config->isSubmissionOpen(now()));
    }
}
