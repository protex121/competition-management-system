<?php

declare(strict_types=1);

namespace Tests\Unit\Policies\Team;

use App\Enums\RegistrationMode;
use App\Enums\TeamStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Policies\Team\TeamPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamPolicyTest extends TestCase
{
    use RefreshDatabase;

    private TeamPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TeamPolicy;
    }

    public function test_participant_can_create_team_in_published_team_mode_competition(): void
    {
        $organization = Organization::factory()->create();
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
        ]);

        $this->assertTrue($this->policy->create($participant, $competition));
    }

    public function test_participant_cannot_create_team_in_individual_mode_competition(): void
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

        $this->assertFalse($this->policy->create($participant, $competition));
    }

    public function test_captain_can_update_forming_team(): void
    {
        $team = Team::factory()->create(['status' => TeamStatus::Forming]);
        $captain = User::query()->findOrFail($team->captain_user_id);

        $this->assertTrue($this->policy->update($captain, $team));
    }

    public function test_captain_cannot_update_approved_team(): void
    {
        $team = Team::factory()->approved()->create();
        $captain = User::query()->findOrFail($team->captain_user_id);

        $this->assertFalse($this->policy->update($captain, $team));
    }

    public function test_organizer_can_approve_pending_team_in_their_org(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
        ]);
        $team = Team::factory()->pendingApproval()->create(['competition_id' => $competition->id]);

        $this->assertTrue($this->policy->approve($organizer, $team));
        $this->assertTrue($this->policy->reject($organizer, $team));
    }

    public function test_organizer_from_other_org_cannot_approve_team(): void
    {
        $team = Team::factory()->pendingApproval()->create();
        $organizer = User::factory()->organizer()->create();

        $this->assertFalse($this->policy->approve($organizer, $team));
    }

    public function test_member_can_view_team_but_not_invite(): void
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
        ]);

        $this->assertTrue($this->policy->view($member, $team));
        $this->assertFalse($this->policy->invite($member, $team));
    }

    public function test_captain_can_submit_forming_team(): void
    {
        $team = Team::factory()->create(['status' => TeamStatus::Forming]);
        $captain = User::query()->findOrFail($team->captain_user_id);

        $this->assertTrue($this->policy->submit($captain, $team));
    }
}
