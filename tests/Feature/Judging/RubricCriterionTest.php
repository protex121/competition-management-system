<?php

declare(strict_types=1);

namespace Tests\Feature\Judging;

use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\Score;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RubricCriterionTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_create_update_and_delete_a_criterion(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($organizer)
            ->post(route('competitions.rubric-criteria.store', $competition), [
                'name' => 'Innovation',
                'max_score' => 10,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rubric_criteria', ['name' => 'Innovation', 'max_score' => 10]);

        $criterion = RubricCriterion::query()->where('name', 'Innovation')->firstOrFail();

        $this->actingAs($organizer)
            ->put(route('rubric-criteria.update', $criterion), [
                'name' => 'Innovation & Originality',
                'max_score' => 15,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rubric_criteria', ['id' => $criterion->id, 'name' => 'Innovation & Originality', 'max_score' => 15]);

        $this->actingAs($organizer)
            ->delete(route('rubric-criteria.destroy', $criterion))
            ->assertRedirect();

        $this->assertDatabaseMissing('rubric_criteria', ['id' => $criterion->id]);
    }

    public function test_creating_a_criterion_auto_creates_the_rubric_when_missing(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        // Created directly via factory, bypassing CreateCompetitionService's auto-provisioning.
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);

        $this->assertDatabaseMissing('rubrics', ['competition_id' => $competition->id]);

        $this->actingAs($organizer)
            ->post(route('competitions.rubric-criteria.store', $competition), [
                'name' => 'Design',
                'max_score' => 5,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rubrics', ['competition_id' => $competition->id]);
    }

    public function test_delete_is_blocked_when_criterion_already_has_scores(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);
        $rubric = Rubric::factory()->create(['competition_id' => $competition->id]);
        $criterion = RubricCriterion::factory()->create(['rubric_id' => $rubric->id]);
        $category = CompetitionCategory::factory()->active()->create(['competition_id' => $competition->id]);
        $registration = Registration::factory()->create(['competition_category_id' => $category->id]);
        $submission = Submission::factory()->finalized()->create(['registration_id' => $registration->id]);
        Score::factory()->create([
            'submission_id' => $submission->id,
            'rubric_criterion_id' => $criterion->id,
        ]);

        $this->actingAs($organizer)
            ->delete(route('rubric-criteria.destroy', $criterion))
            ->assertSessionHasErrors('criterion');

        $this->assertDatabaseHas('rubric_criteria', ['id' => $criterion->id]);
    }

    public function test_non_organizer_cannot_manage_criteria(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);
        $participant = User::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($participant)
            ->post(route('competitions.rubric-criteria.store', $competition), [
                'name' => 'Design',
                'max_score' => 5,
            ])
            ->assertForbidden();
    }
}
