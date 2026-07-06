<?php

declare(strict_types=1);

namespace Tests\Unit\Policies\Team;

use App\Enums\InvitationStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Organization;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Policies\Team\TeamInvitationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamInvitationPolicyTest extends TestCase
{
    use RefreshDatabase;

    private TeamInvitationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TeamInvitationPolicy;
    }

    public function test_invitee_can_accept_pending_invitation(): void
    {
        $invitee = User::factory()->create(['role' => UserRole::Participant]);
        $invitation = TeamInvitation::factory()->create([
            'invited_user_id' => $invitee->id,
            'status' => InvitationStatus::Pending,
            'expires_at' => now()->addDay(),
        ]);

        $this->assertTrue($this->policy->accept($invitee, $invitation));
        $this->assertTrue($this->policy->decline($invitee, $invitation));
    }

    public function test_invitee_cannot_accept_expired_invitation(): void
    {
        $invitee = User::factory()->create(['role' => UserRole::Participant]);
        $invitation = TeamInvitation::factory()->create([
            'invited_user_id' => $invitee->id,
            'status' => InvitationStatus::Pending,
            'expires_at' => now()->subDay(),
        ]);

        $this->assertFalse($this->policy->accept($invitee, $invitation));
    }

    public function test_captain_can_revoke_pending_invitation(): void
    {
        $team = Team::factory()->create();
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'status' => InvitationStatus::Pending,
        ]);
        $captain = User::query()->findOrFail($team->captain_user_id);

        $this->assertTrue($this->policy->revoke($captain, $invitation));
    }

    public function test_organizer_in_same_org_can_revoke_invitation(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
        ]);
        $team = Team::factory()->create(['competition_id' => $competition->id]);
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'status' => InvitationStatus::Pending,
        ]);

        $this->assertTrue($this->policy->revoke($organizer, $invitation));
    }

    public function test_outsider_cannot_revoke_invitation(): void
    {
        $invitation = TeamInvitation::factory()->create(['status' => InvitationStatus::Pending]);
        $outsider = User::factory()->create(['role' => UserRole::Participant]);

        $this->assertFalse($this->policy->revoke($outsider, $invitation));
    }
}
