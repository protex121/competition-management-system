<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Team;

use App\Enums\InvitationStatus;
use App\Enums\RegistrationMode;
use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Enums\TeamStatus;
use App\Models\Competition;
use App\Models\ParticipantProfile;
use App\Models\Scopes\OrganizationScope;
use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamFoundationModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_profile_factory_persists(): void
    {
        $profile = ParticipantProfile::factory()->create([
            'bio' => 'Hello world',
        ]);

        $this->assertDatabaseHas('participant_profiles', [
            'id' => $profile->id,
            'bio' => 'Hello world',
        ]);
    }

    public function test_team_factory_creates_captain_membership(): void
    {
        $team = Team::factory()->create();

        $competition = Competition::withoutGlobalScope(OrganizationScope::class)
            ->findOrFail($team->competition_id);

        $this->assertSame(TeamStatus::Forming, $team->status);
        $this->assertSame(RegistrationMode::Team, $competition->registration_mode);

        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $team->captain_user_id,
            'role' => TeamMemberRole::Captain->value,
            'status' => TeamMemberStatus::Active->value,
        ]);
    }

    public function test_team_invitation_factory_sets_email_from_invitee(): void
    {
        $invitation = TeamInvitation::factory()->create();

        $this->assertSame(
            $invitation->invitedUser->email,
            $invitation->email,
        );
        $this->assertSame(InvitationStatus::Pending, $invitation->status);
    }

    public function test_competition_defaults_to_individual_registration_mode(): void
    {
        $competition = Competition::factory()->create();

        $this->assertSame(RegistrationMode::Individual, $competition->registration_mode);
        $this->assertFalse($competition->requires_coach);
    }
}
