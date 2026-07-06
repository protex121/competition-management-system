<?php

declare(strict_types=1);

namespace Tests\Feature\Team;

use App\Enums\TeamMemberRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Team\Concerns\CreatesTeamFixtures;
use Tests\TestCase;

class TeamMembershipTest extends TestCase
{
    use CreatesTeamFixtures;
    use RefreshDatabase;

    public function test_captain_can_transfer_captaincy(): void
    {
        [$organization, $competition] = $this->createTeamCompetitionContext();
        $captain = $this->createParticipantFor($organization);
        $member = $this->createParticipantFor($organization);
        $team = $this->createTeamForCaptain($competition, $captain);
        $memberRecord = $this->addTeamMember($team, $member);

        $this->actingAs($captain)
            ->post(route('teams.members.transfer-captain', [$team, $memberRecord]))
            ->assertRedirect(route('teams.show', $team));

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'captain_user_id' => $member->id,
        ]);

        $this->assertDatabaseHas('team_members', [
            'id' => $memberRecord->id,
            'role' => TeamMemberRole::Captain->value,
        ]);
    }

    public function test_captain_can_remove_member(): void
    {
        [$organization, $competition] = $this->createTeamCompetitionContext();
        $captain = $this->createParticipantFor($organization);
        $member = $this->createParticipantFor($organization);
        $team = $this->createTeamForCaptain($competition, $captain);
        $memberRecord = $this->addTeamMember($team, $member);

        $this->actingAs($captain)
            ->delete(route('teams.members.destroy', [$team, $memberRecord]))
            ->assertRedirect(route('teams.show', $team));

        $this->assertDatabaseHas('team_members', [
            'id' => $memberRecord->id,
            'status' => 'removed',
        ]);
    }

    public function test_member_can_leave_team(): void
    {
        [$organization, $competition] = $this->createTeamCompetitionContext();
        $captain = $this->createParticipantFor($organization);
        $member = $this->createParticipantFor($organization);
        $team = $this->createTeamForCaptain($competition, $captain);
        $memberRecord = $this->addTeamMember($team, $member);

        $this->actingAs($member)
            ->post(route('teams.leave', $team))
            ->assertRedirect(route('competitions.teams.index', $competition));

        $this->assertDatabaseHas('team_members', [
            'id' => $memberRecord->id,
            'status' => 'removed',
        ]);
    }

    public function test_captain_cannot_leave_without_transfer(): void
    {
        $team = Team::factory()->create();
        $captain = User::query()->findOrFail($team->captain_user_id);

        $this->actingAs($captain)
            ->post(route('teams.leave', $team))
            ->assertSessionHasErrors('team');
    }
}
