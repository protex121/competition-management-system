<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Leaderboard;

use App\Enums\UserRole;
use App\Models\CompetitionCategory;
use App\Models\LeaderboardEntry;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\RubricCriterion;
use App\Models\Score;
use App\Models\Submission;
use App\Models\User;
use App\Services\Leaderboard\CalculateCategoryLeaderboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Judging\Concerns\CreatesJudgingFixtures;
use Tests\TestCase;

class CalculateCategoryLeaderboardServiceTest extends TestCase
{
    use CreatesJudgingFixtures;
    use RefreshDatabase;

    private CalculateCategoryLeaderboardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CalculateCategoryLeaderboardService;
    }

    public function test_ranks_submissions_by_average_judge_total_descending(): void
    {
        [$organization, $competition, $category, , $submissionA] = $this->createFinalizedSubmissionSetup();
        $rubric = $this->createRubricWithCriteria($competition, [
            ['name' => 'Innovation', 'max_score' => 10],
            ['name' => 'Execution', 'max_score' => 10],
        ]);
        [$innovation, $execution] = $rubric->criteria;

        $submissionB = $this->createSecondFinalizedSubmission($organization, $category);

        $judgeOne = $this->createAssignedJudge($organization, $competition);
        $judgeTwo = $this->createAssignedJudge($organization, $competition);

        // Submission A: judge totals 15 and 17 -> average 16.
        $this->createScore($submissionA, $innovation, $judgeOne, 8);
        $this->createScore($submissionA, $execution, $judgeOne, 7);
        $this->createScore($submissionA, $innovation, $judgeTwo, 9);
        $this->createScore($submissionA, $execution, $judgeTwo, 8);

        // Submission B: judge total 12 (one judge only) -> average 12.
        $this->createScore($submissionB, $innovation, $judgeOne, 6);
        $this->createScore($submissionB, $execution, $judgeOne, 6);

        $this->service->execute($category);

        $entries = LeaderboardEntry::withoutGlobalScopes()
            ->where('competition_category_id', $category->id)
            ->orderBy('rank')
            ->get();

        $this->assertCount(2, $entries);
        $this->assertSame($submissionA->id, $entries[0]->submission_id);
        $this->assertSame(1, $entries[0]->rank);
        $this->assertEquals(16.0, (float) $entries[0]->aggregate_score);
        $this->assertSame(2, $entries[0]->judge_count);

        $this->assertSame($submissionB->id, $entries[1]->submission_id);
        $this->assertSame(2, $entries[1]->rank);
        $this->assertEquals(12.0, (float) $entries[1]->aggregate_score);
        $this->assertSame(1, $entries[1]->judge_count);
    }

    public function test_ties_are_broken_by_earliest_submission(): void
    {
        [$organization, $competition, $category, , $submissionA] = $this->createFinalizedSubmissionSetup();
        $submissionA->update(['submitted_at' => now()->subDay()]);
        $rubric = $this->createRubricWithCriteria($competition, [['name' => 'Innovation', 'max_score' => 10]]);
        $criterion = $rubric->criteria->first();

        $submissionB = $this->createSecondFinalizedSubmission($organization, $category);
        $submissionB->update(['submitted_at' => now()]);

        $judge = $this->createAssignedJudge($organization, $competition);

        $this->createScore($submissionA, $criterion, $judge, 5);
        $this->createScore($submissionB, $criterion, $judge, 5);

        $this->service->execute($category);

        $entries = LeaderboardEntry::withoutGlobalScopes()
            ->where('competition_category_id', $category->id)
            ->orderBy('rank')
            ->get();

        $this->assertSame($submissionA->id, $entries[0]->submission_id);
        $this->assertSame($submissionB->id, $entries[1]->submission_id);
    }

    public function test_unscored_submissions_are_excluded(): void
    {
        [$organization, $competition, $category, , $submissionA] = $this->createFinalizedSubmissionSetup();
        $rubric = $this->createRubricWithCriteria($competition, [['name' => 'Innovation', 'max_score' => 10]]);
        $criterion = $rubric->criteria->first();

        // Unscored second submission — must never appear.
        $this->createSecondFinalizedSubmission($organization, $category);

        $judge = $this->createAssignedJudge($organization, $competition);
        $this->createScore($submissionA, $criterion, $judge, 5);

        $this->service->execute($category);

        $entries = LeaderboardEntry::withoutGlobalScopes()
            ->where('competition_category_id', $category->id)
            ->get();

        $this->assertCount(1, $entries);
        $this->assertSame($submissionA->id, $entries[0]->submission_id);
    }

    public function test_recomputing_replaces_previous_entries(): void
    {
        [$organization, $competition, $category, , $submissionA] = $this->createFinalizedSubmissionSetup();
        $rubric = $this->createRubricWithCriteria($competition, [['name' => 'Innovation', 'max_score' => 10]]);
        $criterion = $rubric->criteria->first();
        $judge = $this->createAssignedJudge($organization, $competition);
        $this->createScore($submissionA, $criterion, $judge, 5);

        $this->service->execute($category);
        $this->service->execute($category);

        $this->assertSame(
            1,
            LeaderboardEntry::withoutGlobalScopes()->where('competition_category_id', $category->id)->count(),
        );
    }

    private function createSecondFinalizedSubmission(Organization $organization, CompetitionCategory $category): Submission
    {
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);
        $registration = Registration::factory()->create([
            'competition_category_id' => $category->id,
            'user_id' => $participant->id,
        ]);

        return Submission::factory()->finalized()->create([
            'registration_id' => $registration->id,
            'description' => 'Another entry.',
        ]);
    }

    private function createScore(Submission $submission, RubricCriterion $criterion, User $judge, int $score): Score
    {
        return Score::factory()->create([
            'submission_id' => $submission->id,
            'rubric_criterion_id' => $criterion->id,
            'judge_id' => $judge->id,
            'score' => $score,
        ]);
    }
}
