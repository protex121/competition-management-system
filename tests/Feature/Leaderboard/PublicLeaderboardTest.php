<?php

declare(strict_types=1);

namespace Tests\Feature\Leaderboard;

use App\Enums\CompetitionStatus;
use App\Models\LeaderboardEntry;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Judging\Concerns\CreatesJudgingFixtures;
use Tests\TestCase;

class PublicLeaderboardTest extends TestCase
{
    use CreatesJudgingFixtures;
    use RefreshDatabase;

    public function test_leaderboard_is_not_available_before_competition_closes(): void
    {
        [$organization, $competition] = $this->createFinalizedSubmissionSetup();

        $this->get(route('events.competitions.leaderboard', [
            'organization' => $organization->slug,
            'competition' => $competition->slug,
        ]))->assertNotFound();
    }

    public function test_guest_can_view_leaderboard_after_close(): void
    {
        [$organization, $competition, $category, , $submission] = $this->createFinalizedSubmissionSetup();
        $competition->update(['status' => CompetitionStatus::Closed]);

        LeaderboardEntry::factory()->create([
            'competition_category_id' => $category->id,
            'submission_id' => $submission->id,
            'aggregate_score' => 8.5,
            'judge_count' => 2,
            'rank' => 1,
        ]);

        $this->get(route('events.competitions.leaderboard', [
            'organization' => $organization->slug,
            'competition' => $competition->slug,
        ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('leaderboard/Show', shouldExist: false)
                ->has('categories', 1)
                ->where('categories.0.entries.0.rank', 1)
                ->where('categories.0.entries.0.aggregate_score', 8.5)
                ->where('categories.0.entries.0.judge_count', 2)
                ->where('categories.0.entries.0.type', 'individual')
            );
    }

    public function test_category_with_no_scored_submissions_has_empty_entries(): void
    {
        [$organization, $competition, $category] = $this->createFinalizedSubmissionSetup();
        $competition->update(['status' => CompetitionStatus::Closed]);

        $this->get(route('events.competitions.leaderboard', [
            'organization' => $organization->slug,
            'competition' => $competition->slug,
        ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('categories', 1)
                ->has('categories.0.entries', 0)
            );
    }

    public function test_unknown_competition_slug_returns_not_found(): void
    {
        [$organization, $competition] = $this->createFinalizedSubmissionSetup();
        $competition->update(['status' => CompetitionStatus::Closed]);

        $this->get(route('events.competitions.leaderboard', [
            'organization' => $organization->slug,
            'competition' => 'missing-event',
        ]))->assertNotFound();
    }

    public function test_competition_slug_is_scoped_to_organization(): void
    {
        [$organization, $competition] = $this->createFinalizedSubmissionSetup();
        $competition->update(['status' => CompetitionStatus::Closed]);
        $otherOrganization = Organization::factory()->create();

        $this->get(route('events.competitions.leaderboard', [
            'organization' => $otherOrganization->slug,
            'competition' => $competition->slug,
        ]))->assertNotFound();
    }
}
