<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Team;

use App\Enums\RegistrationMode;
use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Enums\TeamStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\Team\CreateTeamService;
use App\Services\Team\DeleteTeamService;
use App\Services\Team\ListTeamsService;
use App\Services\Team\ShowTeamService;
use App\Services\Team\UpdateTeamService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeamCrudServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_team_service_creates_team_with_captain_member(): void
    {
        $organization = Organization::factory()->create();
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
        ]);

        $team = (new CreateTeamService)->execute($participant, $competition, [
            'name' => 'Alpha Squad',
        ]);

        $this->assertSame('Alpha Squad', $team->name);
        $this->assertSame(TeamStatus::Forming, $team->status);
        $this->assertSame($participant->id, $team->captain_user_id);
        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $participant->id,
            'role' => TeamMemberRole::Captain->value,
            'status' => TeamMemberStatus::Active->value,
        ]);
    }

    public function test_create_team_service_blocks_second_team_in_same_competition(): void
    {
        $organization = Organization::factory()->create();
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
        ]);

        (new CreateTeamService)->execute($participant, $competition, ['name' => 'First Team']);

        $this->expectException(ValidationException::class);

        (new CreateTeamService)->execute($participant, $competition, ['name' => 'Second Team']);
    }

    public function test_create_team_service_denies_individual_mode_competition(): void
    {
        $organization = Organization::factory()->create();
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);
        $competition = Competition::factory()->published()->create([
            'organization_id' => $organization->id,
            'registration_mode' => RegistrationMode::Individual,
        ]);

        $this->expectException(AuthorizationException::class);

        (new CreateTeamService)->execute($participant, $competition, ['name' => 'Alpha Squad']);
    }

    public function test_update_team_service_updates_name_when_forming(): void
    {
        $team = Team::factory()->create(['status' => TeamStatus::Forming]);
        $captain = User::query()->findOrFail($team->captain_user_id);

        $updated = (new UpdateTeamService)->execute($captain, $team, [
            'name' => 'Renamed Squad',
        ]);

        $this->assertSame('Renamed Squad', $updated->name);
    }

    public function test_update_team_service_denies_non_captain(): void
    {
        $team = Team::factory()->create();
        $competition = Competition::withoutGlobalScope(OrganizationScope::class)
            ->findOrFail($team->competition_id);
        $member = User::factory()->create([
            'organization_id' => $competition->organization_id,
            'role' => UserRole::Participant,
        ]);
        TeamMember::factory()->create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => TeamMemberRole::Member,
            'status' => TeamMemberStatus::Active,
        ]);

        $this->expectException(AuthorizationException::class);

        (new UpdateTeamService)->execute($member, $team, ['name' => 'Hijacked']);
    }

    public function test_delete_team_service_soft_deletes_forming_team(): void
    {
        $team = Team::factory()->create(['status' => TeamStatus::Forming]);
        $captain = User::query()->findOrFail($team->captain_user_id);

        (new DeleteTeamService)->execute($captain, $team);

        $this->assertSoftDeleted('teams', ['id' => $team->id]);
    }

    public function test_list_teams_service_scopes_participant_to_own_teams(): void
    {
        $organization = Organization::factory()->create();
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);
        $otherParticipant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
        ]);

        $ownTeam = Team::factory()->create([
            'competition_id' => $competition->id,
            'captain_user_id' => $participant->id,
        ]);
        TeamMember::query()->where('team_id', $ownTeam->id)->delete();
        TeamMember::factory()->create([
            'team_id' => $ownTeam->id,
            'user_id' => $participant->id,
            'role' => TeamMemberRole::Captain,
            'status' => TeamMemberStatus::Active,
        ]);

        Team::factory()->create([
            'competition_id' => $competition->id,
            'captain_user_id' => $otherParticipant->id,
        ]);

        $paginator = (new ListTeamsService)->execute($participant, $competition);

        $this->assertCount(1, $paginator->items());
        $this->assertSame($ownTeam->id, $paginator->items()[0]->id);
    }

    public function test_list_teams_service_lists_all_teams_for_organizer(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
        ]);

        Team::factory()->count(2)->create(['competition_id' => $competition->id]);

        $paginator = (new ListTeamsService)->execute($organizer, $competition);

        $this->assertCount(2, $paginator->items());
    }

    public function test_show_team_service_loads_members_and_pending_invitations(): void
    {
        $team = Team::factory()->create();
        $captain = User::query()->findOrFail($team->captain_user_id);

        $result = (new ShowTeamService)->execute($captain, $team);

        $this->assertTrue($result->relationLoaded('members'));
        $this->assertTrue($result->relationLoaded('invitations'));
        $this->assertTrue($result->relationLoaded('competition'));
    }

    public function test_show_team_service_denies_cross_org_organizer(): void
    {
        $team = Team::factory()->create();
        $organizer = User::factory()->organizer()->create();

        $this->expectException(AuthorizationException::class);

        (new ShowTeamService)->execute($organizer, $team);
    }
}
