<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Team;

use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Scopes\OrganizationScope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\Team\LeaveTeamService;
use App\Services\Team\RemoveTeamMemberService;
use App\Services\Team\TransferCaptainService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeamMembershipServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_captain_service_transfers_captaincy_atomically(): void
    {
        $team = Team::factory()->create();
        $captain = User::query()->findOrFail($team->captain_user_id);
        $competition = Competition::withoutGlobalScope(OrganizationScope::class)
            ->findOrFail($team->competition_id);
        $newCaptain = User::factory()->create([
            'organization_id' => $competition->organization_id,
            'role' => UserRole::Participant,
        ]);
        $newCaptainMember = TeamMember::factory()->create([
            'team_id' => $team->id,
            'user_id' => $newCaptain->id,
            'role' => TeamMemberRole::Member,
            'status' => TeamMemberStatus::Active,
        ]);

        $updated = (new TransferCaptainService)->execute($captain, $team, $newCaptainMember);

        $this->assertSame($newCaptain->id, $updated->captain_user_id);
        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $captain->id,
            'role' => TeamMemberRole::Member->value,
            'status' => TeamMemberStatus::Active->value,
        ]);
        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $newCaptain->id,
            'role' => TeamMemberRole::Captain->value,
            'status' => TeamMemberStatus::Active->value,
        ]);
    }

    public function test_transfer_captain_service_denies_non_manager(): void
    {
        $team = Team::factory()->create();
        $outsider = User::factory()->create();
        $member = TeamMember::factory()->create([
            'team_id' => $team->id,
            'role' => TeamMemberRole::Member,
        ]);

        $this->expectException(AuthorizationException::class);

        (new TransferCaptainService)->execute($outsider, $team, $member);
    }

    public function test_remove_team_member_service_removes_active_member(): void
    {
        $team = Team::factory()->create();
        $captain = User::query()->findOrFail($team->captain_user_id);
        $member = TeamMember::factory()->create([
            'team_id' => $team->id,
            'role' => TeamMemberRole::Member,
            'status' => TeamMemberStatus::Active,
        ]);

        (new RemoveTeamMemberService)->execute($captain, $team, $member);

        $this->assertDatabaseHas('team_members', [
            'id' => $member->id,
            'status' => TeamMemberStatus::Removed->value,
        ]);
    }

    public function test_remove_team_member_service_blocks_captain_removal(): void
    {
        $team = Team::factory()->create();
        $captain = User::query()->findOrFail($team->captain_user_id);
        $captainMember = TeamMember::query()
            ->where('team_id', $team->id)
            ->where('user_id', $captain->id)
            ->firstOrFail();

        $this->expectException(ValidationException::class);

        (new RemoveTeamMemberService)->execute($captain, $team, $captainMember);
    }

    public function test_leave_team_service_allows_member_to_leave(): void
    {
        $team = Team::factory()->create();
        $memberUser = User::factory()->create([
            'organization_id' => Competition::withoutGlobalScope(OrganizationScope::class)
                ->findOrFail($team->competition_id)
                ->organization_id,
            'role' => UserRole::Participant,
        ]);
        $member = TeamMember::factory()->create([
            'team_id' => $team->id,
            'user_id' => $memberUser->id,
            'role' => TeamMemberRole::Member,
            'status' => TeamMemberStatus::Active,
        ]);

        (new LeaveTeamService)->execute($memberUser, $team);

        $this->assertDatabaseHas('team_members', [
            'id' => $member->id,
            'status' => TeamMemberStatus::Removed->value,
        ]);
    }

    public function test_leave_team_service_blocks_captain_from_leaving(): void
    {
        $team = Team::factory()->create();
        $captain = User::query()->findOrFail($team->captain_user_id);

        $this->expectException(ValidationException::class);

        (new LeaveTeamService)->execute($captain, $team);
    }
}
