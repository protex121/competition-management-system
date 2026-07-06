<?php

declare(strict_types=1);

namespace Tests\Feature\Team;

use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Enums\TeamStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Organization;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_captain_can_submit_team_for_approval(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
            'min_team_size' => 2,
        ]);
        $team = Team::factory()->create(['competition_id' => $competition->id]);
        $captain = User::query()->findOrFail($team->captain_user_id);
        $member = User::factory()->create(['organization_id' => $organization->id]);
        TeamMember::factory()->create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => TeamMemberRole::Member,
            'status' => TeamMemberStatus::Active,
        ]);

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
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
        ]);
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
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
        ]);
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);

        $this->actingAs($participant)
            ->get(route('competitions.teams.review', $competition))
            ->assertForbidden();
    }
}
