<?php

declare(strict_types=1);

namespace Tests\Feature\Team;

use App\Enums\InvitationStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_captain_can_send_and_revoke_invitation(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
        ]);
        $captain = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);
        $team = Team::factory()->create([
            'competition_id' => $competition->id,
            'captain_user_id' => $captain->id,
        ]);
        $invitee = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);

        $this->actingAs($captain)
            ->post(route('teams.invitations.store', $team), ['email' => $invitee->email])
            ->assertRedirect(route('teams.show', $team));

        $invitation = TeamInvitation::query()
            ->where('team_id', $team->id)
            ->where('invited_user_id', $invitee->id)
            ->firstOrFail();

        $this->actingAs($captain)
            ->delete(route('teams.invitations.destroy', [$team, $invitation]))
            ->assertRedirect(route('teams.show', $team));

        $this->assertSame(InvitationStatus::Revoked, $invitation->fresh()->status);
    }

    public function test_invitee_can_view_inbox_and_accept_invitation(): void
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
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($invitee)
            ->get(route('invitations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('team/invitations/Index')
                ->has('invitations.data', 1));

        $this->actingAs($invitee)
            ->post(route('invitations.accept', $invitation))
            ->assertRedirect(route('teams.show', $team));

        $this->assertSame(InvitationStatus::Accepted, $invitation->fresh()->status);
        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $invitee->id,
        ]);
    }

    public function test_invitee_can_decline_invitation(): void
    {
        $invitation = TeamInvitation::factory()->create([
            'status' => InvitationStatus::Pending,
            'expires_at' => now()->addDay(),
        ]);
        $invitee = User::query()->findOrFail($invitation->invited_user_id);

        $this->actingAs($invitee)
            ->post(route('invitations.decline', $invitation))
            ->assertRedirect(route('invitations.index'));

        $this->assertSame(InvitationStatus::Declined, $invitation->fresh()->status);
    }
}
