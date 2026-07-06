<?php

declare(strict_types=1);

namespace Tests\Feature\Team;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Team\Concerns\CreatesTeamFixtures;
use Tests\TestCase;

class TeamCoachAssignmentTest extends TestCase
{
    use CreatesTeamFixtures;
    use RefreshDatabase;

    public function test_captain_can_assign_coach(): void
    {
        [$organization, $competition] = $this->createTeamCompetitionContext(['requires_coach' => true]);
        $captain = $this->createParticipantFor($organization);
        $coach = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Coach,
        ]);
        $team = $this->createTeamForCaptain($competition, $captain);

        $this->actingAs($captain)
            ->post(route('teams.coach.store', $team), ['user_id' => $coach->id])
            ->assertRedirect(route('teams.show', $team));

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'coach_user_id' => $coach->id,
        ]);
    }

    public function test_captain_can_remove_coach(): void
    {
        [$organization, $competition] = $this->createTeamCompetitionContext();
        $captain = $this->createParticipantFor($organization);
        $coach = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Coach,
        ]);
        $team = $this->createTeamForCaptain($competition, $captain);
        $team->update(['coach_user_id' => $coach->id]);

        $this->actingAs($captain)
            ->delete(route('teams.coach.destroy', $team))
            ->assertRedirect(route('teams.show', $team));

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'coach_user_id' => null,
        ]);
    }

    public function test_team_show_includes_coach_options_for_captain(): void
    {
        [$organization, $competition] = $this->createTeamCompetitionContext();
        $captain = $this->createParticipantFor($organization);
        User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Coach,
        ]);
        $team = $this->createTeamForCaptain($competition, $captain);

        $this->actingAs($captain)
            ->get(route('teams.show', $team))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('can.assignCoach', true)
                ->has('availableCoaches', 1));
    }
}
