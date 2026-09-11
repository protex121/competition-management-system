<?php

declare(strict_types=1);

namespace Tests\Feature\Judging;

use App\Enums\CompetitionStatus;
use App\Enums\SubmissionStatus;
use App\Enums\UserRole;
use App\Models\CompetitionJudge;
use App\Models\Registration;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Judging\Concerns\CreatesJudgingFixtures;
use Tests\TestCase;

class ScoringTest extends TestCase
{
    use CreatesJudgingFixtures;
    use RefreshDatabase;

    public function test_assigned_judge_can_submit_full_scorecard(): void
    {
        [$organization, $competition, , , $submission] = $this->createFinalizedSubmissionSetup();
        $rubric = $this->createRubricWithCriteria($competition, [
            ['name' => 'Innovation', 'max_score' => 10],
            ['name' => 'Execution', 'max_score' => 5],
        ]);
        $judge = $this->createAssignedJudge($organization, $competition);
        $criteria = $rubric->criteria;

        $this->actingAs($judge)
            ->get(route('submissions.score.edit', $submission))
            ->assertOk();

        $this->actingAs($judge)
            ->put(route('submissions.score.update', $submission), [
                'entries' => [
                    ['rubric_criterion_id' => $criteria[0]->id, 'score' => 8, 'comment' => 'Great idea'],
                    ['rubric_criterion_id' => $criteria[1]->id, 'score' => 4],
                ],
            ])
            ->assertRedirect(route('judging.queue.index'));

        $this->assertDatabaseHas('scores', [
            'submission_id' => $submission->id,
            'rubric_criterion_id' => $criteria[0]->id,
            'judge_id' => $judge->id,
            'score' => 8,
        ]);
        $this->assertDatabaseHas('scores', [
            'submission_id' => $submission->id,
            'rubric_criterion_id' => $criteria[1]->id,
            'judge_id' => $judge->id,
            'score' => 4,
        ]);
    }

    public function test_resubmitting_a_scorecard_updates_in_place(): void
    {
        [$organization, $competition, , , $submission] = $this->createFinalizedSubmissionSetup();
        $rubric = $this->createRubricWithCriteria($competition);
        $judge = $this->createAssignedJudge($organization, $competition);
        $criterion = $rubric->criteria->first();

        $this->actingAs($judge)->put(route('submissions.score.update', $submission), [
            'entries' => [['rubric_criterion_id' => $criterion->id, 'score' => 3]],
        ])->assertRedirect();

        $this->actingAs($judge)->put(route('submissions.score.update', $submission), [
            'entries' => [['rubric_criterion_id' => $criterion->id, 'score' => 9]],
        ])->assertRedirect();

        $this->assertDatabaseCount('scores', 1);
        $this->assertDatabaseHas('scores', [
            'submission_id' => $submission->id,
            'rubric_criterion_id' => $criterion->id,
            'judge_id' => $judge->id,
            'score' => 9,
        ]);
    }

    public function test_judge_cannot_score_their_own_submission(): void
    {
        [$organization, $competition, , , $submission, $participant] = $this->createFinalizedSubmissionSetup();
        $rubric = $this->createRubricWithCriteria($competition);
        $criterion = $rubric->criteria->first();

        $participant->update(['role' => UserRole::Judge]);
        CompetitionJudge::factory()->create([
            'competition_id' => $competition->id,
            'user_id' => $participant->id,
        ]);

        $this->actingAs($participant)
            ->get(route('submissions.score.edit', $submission))
            ->assertForbidden();

        $this->actingAs($participant)
            ->put(route('submissions.score.update', $submission), [
                'entries' => [['rubric_criterion_id' => $criterion->id, 'score' => 5]],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('scores', 0);
    }

    public function test_unassigned_judge_cannot_score(): void
    {
        [$organization, $competition, , , $submission] = $this->createFinalizedSubmissionSetup();
        $rubric = $this->createRubricWithCriteria($competition);
        $criterion = $rubric->criteria->first();
        $judge = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Judge,
        ]);

        $this->actingAs($judge)
            ->put(route('submissions.score.update', $submission), [
                'entries' => [['rubric_criterion_id' => $criterion->id, 'score' => 5]],
            ])
            ->assertForbidden();
    }

    public function test_cannot_score_non_finalized_submission(): void
    {
        [$organization, $competition, $category, $registration] = $this->createFinalizedSubmissionSetup();
        $submission = Submission::withoutGlobalScopes()->where('registration_id', $registration->id)->firstOrFail();
        $submission->update(['status' => SubmissionStatus::Draft]);
        $rubric = $this->createRubricWithCriteria($competition);
        $criterion = $rubric->criteria->first();
        $judge = $this->createAssignedJudge($organization, $competition);

        $this->actingAs($judge)
            ->put(route('submissions.score.update', $submission), [
                'entries' => [['rubric_criterion_id' => $criterion->id, 'score' => 5]],
            ])
            ->assertForbidden();
    }

    public function test_cannot_score_when_competition_closed(): void
    {
        [$organization, $competition, , , $submission] = $this->createFinalizedSubmissionSetup();
        $rubric = $this->createRubricWithCriteria($competition);
        $criterion = $rubric->criteria->first();
        $judge = $this->createAssignedJudge($organization, $competition);

        $competition->update(['status' => CompetitionStatus::Closed]);

        $this->actingAs($judge)
            ->put(route('submissions.score.update', $submission), [
                'entries' => [['rubric_criterion_id' => $criterion->id, 'score' => 5]],
            ])
            ->assertForbidden();
    }

    public function test_out_of_range_score_is_rejected(): void
    {
        [$organization, $competition, , , $submission] = $this->createFinalizedSubmissionSetup();
        $rubric = $this->createRubricWithCriteria($competition, [['name' => 'Innovation', 'max_score' => 10]]);
        $criterion = $rubric->criteria->first();
        $judge = $this->createAssignedJudge($organization, $competition);

        $this->actingAs($judge)
            ->put(route('submissions.score.update', $submission), [
                'entries' => [['rubric_criterion_id' => $criterion->id, 'score' => 11]],
            ])
            ->assertSessionHasErrors('score');

        $this->assertDatabaseCount('scores', 0);
    }

    public function test_missing_criterion_is_rejected(): void
    {
        [$organization, $competition, , , $submission] = $this->createFinalizedSubmissionSetup();
        $rubric = $this->createRubricWithCriteria($competition, [
            ['name' => 'Innovation', 'max_score' => 10],
            ['name' => 'Execution', 'max_score' => 5],
        ]);
        $criteria = $rubric->criteria;
        $judge = $this->createAssignedJudge($organization, $competition);

        $this->actingAs($judge)
            ->put(route('submissions.score.update', $submission), [
                'entries' => [['rubric_criterion_id' => $criteria[0]->id, 'score' => 5]],
            ])
            ->assertSessionHasErrors('score');

        $this->assertDatabaseCount('scores', 0);
    }

    public function test_queue_excludes_own_submission_and_shows_scored_flag(): void
    {
        [$organization, $competition, $category, , $ownSubmission, $participant] = $this->createFinalizedSubmissionSetup();
        $rubric = $this->createRubricWithCriteria($competition);
        $criterion = $rubric->criteria->first();

        $otherParticipant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);
        $otherRegistration = Registration::factory()->create([
            'competition_category_id' => $category->id,
            'user_id' => $otherParticipant->id,
        ]);
        $otherSubmission = Submission::factory()->finalized()->create([
            'registration_id' => $otherRegistration->id,
            'description' => 'Another entry.',
        ]);

        // The participant is also assigned as a judge on the same competition.
        $participant->update(['role' => UserRole::Judge]);
        CompetitionJudge::factory()->create([
            'competition_id' => $competition->id,
            'user_id' => $participant->id,
        ]);

        // A second, unrelated judge sees both submissions (owns neither).
        $judge = $this->createAssignedJudge($organization, $competition);

        $this->actingAs($judge)
            ->get(route('judging.queue.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('judging/Queue', shouldExist: false)
                ->has('submissions', 2));

        // The participant-turned-judge only ever sees the OTHER submission, never their own.
        $this->actingAs($participant)
            ->get(route('judging.queue.index'))
            ->assertInertia(fn ($page) => $page
                ->has('submissions', 1)
                ->where('submissions.0.id', $otherSubmission->id)
                ->where('submissions.0.has_scored', false));

        $this->actingAs($participant)->put(route('submissions.score.update', $otherSubmission), [
            'entries' => [['rubric_criterion_id' => $criterion->id, 'score' => 5]],
        ])->assertRedirect();

        $this->actingAs($participant)
            ->get(route('judging.queue.index'))
            ->assertInertia(fn ($page) => $page
                ->has('submissions', 1)
                ->where('submissions.0.has_scored', true));
    }

    public function test_organizer_sees_score_count_on_review_page(): void
    {
        [$organization, $competition, $category, , $submission] = $this->createFinalizedSubmissionSetup();
        $rubric = $this->createRubricWithCriteria($competition);
        $criterion = $rubric->criteria->first();
        $judge = $this->createAssignedJudge($organization, $competition);
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);

        $this->actingAs($judge)->put(route('submissions.score.update', $submission), [
            'entries' => [['rubric_criterion_id' => $criterion->id, 'score' => 5]],
        ])->assertRedirect();

        $this->actingAs($organizer)
            ->get(route('competitions.categories.submissions.index', [$competition, $category]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('submissions.0.score_count', 1));
    }
}
