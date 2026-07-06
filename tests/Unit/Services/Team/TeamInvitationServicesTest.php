<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Team;

use App\Enums\InvitationStatus;
use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\Team\RevokeTeamInvitationService;
use App\Services\Team\SendTeamInvitationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeamInvitationServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_invitation_service_creates_pending_invitation(): void
    {
        $organization = Organization::factory()->create();
        $team = Team::factory()->create();
        $captain = User::query()->findOrFail($team->captain_user_id);
        $invitee = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);
        Competition::withoutGlobalScope(OrganizationScope::class)
            ->where('id', $team->competition_id)
            ->update(['organization_id' => $organization->id]);

        $invitation = (new SendTeamInvitationService)->execute($captain, $team, [
            'email' => $invitee->email,
        ]);

        $this->assertSame(InvitationStatus::Pending, $invitation->status);
        $this->assertSame($invitee->id, $invitation->invited_user_id);
        $this->assertNotEmpty($invitation->token);
    }

    public function test_send_invitation_service_blocks_full_team(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
            'min_team_size' => 2,
            'max_team_size' => 2,
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
        $invitee = User::factory()->create(['organization_id' => $organization->id]);

        $this->expectException(ValidationException::class);

        (new SendTeamInvitationService)->execute($captain, $team, [
            'email' => $invitee->email,
        ]);
    }

    public function test_send_invitation_service_blocks_duplicate_pending(): void
    {
        $organization = Organization::factory()->create();
        $team = Team::factory()->create();
        $captain = User::query()->findOrFail($team->captain_user_id);
        $invitee = User::factory()->create(['organization_id' => $organization->id]);
        Competition::withoutGlobalScope(OrganizationScope::class)
            ->where('id', $team->competition_id)
            ->update(['organization_id' => $organization->id]);

        TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'invited_user_id' => $invitee->id,
            'status' => InvitationStatus::Pending,
        ]);

        $this->expectException(ValidationException::class);

        (new SendTeamInvitationService)->execute($captain, $team, [
            'email' => $invitee->email,
        ]);
    }

    public function test_revoke_invitation_service_revokes_pending_invitation(): void
    {
        $invitation = TeamInvitation::factory()->create(['status' => InvitationStatus::Pending]);
        $team = Team::withoutGlobalScopes()->findOrFail($invitation->team_id);
        $captain = User::query()->findOrFail($team->captain_user_id);

        $revoked = (new RevokeTeamInvitationService)->execute($captain, $invitation);

        $this->assertSame(InvitationStatus::Revoked, $revoked->status);
        $this->assertNotNull($revoked->responded_at);
    }

    public function test_revoke_invitation_service_denies_invitee(): void
    {
        $invitation = TeamInvitation::factory()->create();
        $invitee = User::query()->findOrFail($invitation->invited_user_id);

        $this->expectException(AuthorizationException::class);

        (new RevokeTeamInvitationService)->execute($invitee, $invitation);
    }
}
