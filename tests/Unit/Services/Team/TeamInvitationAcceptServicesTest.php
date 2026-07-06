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
use App\Services\Team\AcceptTeamInvitationService;
use App\Services\Team\DeclineTeamInvitationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeamInvitationAcceptServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_accept_invitation_service_adds_member(): void
    {
        $organization = Organization::factory()->create();
        $team = Team::factory()->create();
        $invitee = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);
        Competition::withoutGlobalScope(OrganizationScope::class)
            ->where('id', $team->competition_id)
            ->update(['organization_id' => $organization->id]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'invited_user_id' => $invitee->id,
            'status' => InvitationStatus::Pending,
        ]);

        $member = (new AcceptTeamInvitationService)->execute($invitee, $invitation);

        $this->assertSame($team->id, $member->team_id);
        $this->assertSame(TeamMemberRole::Member, $member->role);
        $this->assertSame(InvitationStatus::Accepted, $invitation->fresh()->status);
    }

    public function test_accept_invitation_service_blocks_second_team_in_competition(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
        ]);
        $team = Team::factory()->create(['competition_id' => $competition->id]);
        $otherTeam = Team::factory()->create(['competition_id' => $competition->id]);
        $invitee = User::factory()->create(['organization_id' => $organization->id]);
        TeamMember::factory()->create([
            'team_id' => $otherTeam->id,
            'user_id' => $invitee->id,
            'role' => TeamMemberRole::Member,
            'status' => TeamMemberStatus::Active,
        ]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'invited_user_id' => $invitee->id,
            'status' => InvitationStatus::Pending,
        ]);

        $this->expectException(ValidationException::class);

        (new AcceptTeamInvitationService)->execute($invitee, $invitation);
    }

    public function test_accept_invitation_service_blocks_expired_invitation(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'expires_at' => now()->subDay(),
            'status' => InvitationStatus::Pending,
        ]);
        $invitee = User::query()->findOrFail($invitation->invited_user_id);

        $this->expectException(AuthorizationException::class);

        (new AcceptTeamInvitationService)->execute($invitee, $invitation);
    }

    public function test_decline_invitation_service_declines_pending_invitation(): void
    {
        $invitation = TeamInvitation::factory()->create(['status' => InvitationStatus::Pending]);
        $invitee = User::query()->findOrFail($invitation->invited_user_id);

        $declined = (new DeclineTeamInvitationService)->execute($invitee, $invitation);

        $this->assertSame(InvitationStatus::Declined, $declined->status);
        $this->assertNotNull($declined->responded_at);
    }

    public function test_decline_invitation_service_denies_non_invitee(): void
    {
        $invitation = TeamInvitation::factory()->create();
        $outsider = User::factory()->create();

        $this->expectException(AuthorizationException::class);

        (new DeclineTeamInvitationService)->execute($outsider, $invitation);
    }
}
