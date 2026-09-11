<?php

declare(strict_types=1);

namespace Tests\Feature\Leaderboard;

use App\Models\LeaderboardEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Judging\Concerns\CreatesJudgingFixtures;
use Tests\TestCase;

class LeaderboardCalculationTest extends TestCase
{
    use CreatesJudgingFixtures;
    use RefreshDatabase;

    public function test_closing_a_competition_computes_the_leaderboard(): void
    {
        [$organization, $competition, $category, , $submission] = $this->createFinalizedSubmissionSetup();
        $rubric = $this->createRubricWithCriteria($competition, [['name' => 'Innovation', 'max_score' => 10]]);
        $criterion = $rubric->criteria->first();
        $judge = $this->createAssignedJudge($organization, $competition);
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);

        $this->actingAs($judge)->put(route('submissions.score.update', $submission), [
            'entries' => [['rubric_criterion_id' => $criterion->id, 'score' => 7]],
        ])->assertRedirect();

        $this->assertDatabaseCount('leaderboard_entries', 0);

        $this->actingAs($organizer)
            ->patch(route('competitions.close', $competition))
            ->assertRedirect(route('competitions.edit', $competition));

        $entry = LeaderboardEntry::withoutGlobalScopes()
            ->where('competition_category_id', $category->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame($submission->id, $entry->submission_id);
        $this->assertSame(1, $entry->rank);
        $this->assertEquals(7.0, (float) $entry->aggregate_score);
        $this->assertSame(1, $entry->judge_count);
    }

    public function test_closing_a_competition_with_no_scores_produces_no_entries(): void
    {
        [$organization, $competition] = $this->createFinalizedSubmissionSetup();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);

        $this->actingAs($organizer)
            ->patch(route('competitions.close', $competition))
            ->assertRedirect(route('competitions.edit', $competition));

        $this->assertDatabaseCount('leaderboard_entries', 0);
    }
}
