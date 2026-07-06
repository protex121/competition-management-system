<?php

declare(strict_types=1);

namespace Tests\Feature\Team;

use App\Enums\TeamStatus;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Team\Concerns\CreatesTeamFixtures;
use Tests\TestCase;

class TeamApprovalTest extends TestCase
{
    use CreatesTeamFixtures;
    use RefreshDatabase;

    public function test_captain_can_submit_team_for_approval(): void
    {
        [$organization, $competition] = $this->createTeamCompetitionContext(['min_team_size' => 2]);
        $team = Team::factory()->create(['competition_id' => $competition->id]);
        $captain = User::query()->findOrFail($team->captain_user_id);
        $member = $this->createParticipantFor($organization);
        $this->addTeamMember($team, $member);

        $this->actingAs($captain)
            ->post(route('teams.submit', $team))
            ->assertRedirect(route('teams.show', $team));

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'status' => TeamStatus::PendingApproval->value,
        ]);
    }

    public function test_organizer_can_review_approve_and_reject_teams(): void
    {
        [$organization, $competition] = $this->createTeamCompetitionContext();
        $organizer = $this->createOrganizerFor($organization);
        $pendingTeam = Team::factory()->pendingApproval()->create(['competition_id' => $competition->id]);
        $rejectTeam = Team::factory()->pendingApproval()->create(['competition_id' => $competition->id]);

        $this->actingAs($organizer)
            ->get(route('competitions.teams.review', $competition))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('team/teams/Review')
                ->has('teams.data', 2));

        $this->actingAs($organizer)
            ->post(route('teams.approve', $pendingTeam))
            ->assertRedirect(route('competitions.teams.review', $competition));

        $this->assertDatabaseHas('teams', [
            'id' => $pendingTeam->id,
            'status' => TeamStatus::Approved->value,
        ]);

        $this->actingAs($organizer)
            ->post(route('teams.reject', $rejectTeam), [
                'rejection_reason' => 'Needs more members',
            ])
            ->assertRedirect(route('competitions.teams.review', $competition));

        $this->assertDatabaseHas('teams', [
            'id' => $rejectTeam->id,
            'status' => TeamStatus::Rejected->value,
            'rejection_reason' => 'Needs more members',
        ]);
    }

    public function test_participant_cannot_access_team_review_page(): void
    {
        [$organization, $competition] = $this->createTeamCompetitionContext();
        $participant = $this->createParticipantFor($organization);

        $this->actingAs($participant)
            ->get(route('competitions.teams.review', $competition))
            ->assertForbidden();
    }
}
