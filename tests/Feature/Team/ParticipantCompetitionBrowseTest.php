<?php

declare(strict_types=1);

namespace Tests\Feature\Team;

use App\Enums\InvitationStatus;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Team\Concerns\CreatesTeamFixtures;
use Tests\TestCase;

class ParticipantCompetitionBrowseTest extends TestCase
{
    use CreatesTeamFixtures;
    use RefreshDatabase;

    public function test_participant_can_browse_open_competitions(): void
    {
        [$organization, $competition] = $this->createTeamCompetitionContext();
        $participant = $this->createParticipantFor($organization);

        $this->actingAs($participant)
            ->get(route('participant.competitions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('participant/competitions/Index')
                ->has('competitions.data', 1)
                ->where('competitions.data.0.id', $competition->id)
                ->where('competitions.data.0.allows_teams', true)
            );
    }

    public function test_participant_sees_their_team_on_browse_page(): void
    {
        [$organization, $competition] = $this->createTeamCompetitionContext();
        $participant = $this->createParticipantFor($organization);
        $team = $this->createTeamForCaptain($competition, $participant);

        $this->actingAs($participant)
            ->get(route('participant.competitions.index'))
            ->assertInertia(fn ($page) => $page
                ->where('competitions.data.0.my_team.id', $team->id)
            );
    }

    public function test_organizer_cannot_access_participant_competition_browse(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get(route('participant.competitions.index'))
            ->assertForbidden();
    }

    public function test_pending_invitation_count_shared_for_participant(): void
    {
        $organization = Organization::factory()->create();
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);
        $team = Team::factory()->create();
        TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'invited_user_id' => $participant->id,
            'status' => InvitationStatus::Pending,
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($participant)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('pendingInvitationsCount', 1));
    }
}
