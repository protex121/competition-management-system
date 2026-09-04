<?php

declare(strict_types=1);

namespace Tests\Unit\Policies\Registration;

use App\Enums\RegistrationMode;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Scopes\OrganizationScope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Policies\Registration\RegistrationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationPolicyTest extends TestCase
{
    use RefreshDatabase;

    private RegistrationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new RegistrationPolicy;
    }

    public function test_participant_can_create_individual_registration_in_active_category(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->published()->create([
            'organization_id' => $organization->id,
            'registration_mode' => RegistrationMode::Individual,
        ]);
        $category = CompetitionCategory::factory()->active()->create([
            'competition_id' => $competition->id,
        ]);
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);

        $this->assertTrue($this->policy->createIndividual($participant, $category));
    }

    public function test_participant_cannot_create_individual_registration_when_mode_is_team_only(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
        ]);
        $category = CompetitionCategory::factory()->active()->create([
            'competition_id' => $competition->id,
        ]);
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);

        $this->assertFalse($this->policy->createIndividual($participant, $category));
    }

    public function test_captain_can_register_approved_team(): void
    {
        $competition = Competition::factory()->teamMode()->published()->create();
        $category = CompetitionCategory::factory()->active()->create([
            'competition_id' => $competition->id,
        ]);
        $team = Team::factory()->approved()->create(['competition_id' => $competition->id]);
        $captain = User::query()->findOrFail($team->captain_user_id);

        $this->assertTrue($this->policy->createForTeam($captain, $team, $category));
    }

    public function test_non_captain_cannot_register_team(): void
    {
        $competition = Competition::factory()->teamMode()->published()->create();
        $category = CompetitionCategory::factory()->active()->create([
            'competition_id' => $competition->id,
        ]);
        $team = Team::factory()->approved()->create(['competition_id' => $competition->id]);
        $organization = Competition::withoutGlobalScope(OrganizationScope::class)
            ->findOrFail($team->competition_id)->organization_id;
        $other = User::factory()->create([
            'organization_id' => $organization,
            'role' => UserRole::Participant,
        ]);

        $this->assertFalse($this->policy->createForTeam($other, $team, $category));
    }

    public function test_cannot_register_team_that_is_not_approved(): void
    {
        $competition = Competition::factory()->teamMode()->published()->create();
        $category = CompetitionCategory::factory()->active()->create([
            'competition_id' => $competition->id,
        ]);
        $team = Team::factory()->create(['competition_id' => $competition->id]);
        $captain = User::query()->findOrFail($team->captain_user_id);

        $this->assertFalse($this->policy->createForTeam($captain, $team, $category));
    }

    public function test_owner_can_withdraw_own_registration(): void
    {
        $registration = Registration::factory()->create();
        $owner = User::query()->findOrFail($registration->user_id);

        $this->assertTrue($this->policy->withdraw($owner, $registration));
    }

    public function test_other_participant_cannot_withdraw_someone_elses_registration(): void
    {
        $registration = Registration::factory()->create();
        $other = User::factory()->create(['role' => UserRole::Participant]);

        $this->assertFalse($this->policy->withdraw($other, $registration));
    }

    public function test_captain_can_withdraw_team_registration(): void
    {
        $team = Team::factory()->approved()->create();
        $registration = Registration::factory()->create([
            'competition_category_id' => CompetitionCategory::factory()->active()->create([
                'competition_id' => $team->competition_id,
            ]),
            'user_id' => null,
            'team_id' => $team->id,
        ]);
        $captain = User::query()->findOrFail($team->captain_user_id);

        $this->assertTrue($this->policy->withdraw($captain, $registration));
    }

    public function test_organizer_can_view_any_registration_in_their_org(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);

        $this->assertTrue($this->policy->viewAny($organizer, $competition));
    }

    public function test_organizer_from_other_org_cannot_view_registrations(): void
    {
        $competition = Competition::factory()->published()->create();
        $organizer = User::factory()->organizer()->create();

        $this->assertFalse($this->policy->viewAny($organizer, $competition));
    }

    public function test_team_member_can_view_team_registration(): void
    {
        $team = Team::factory()->approved()->create();
        $registration = Registration::factory()->create([
            'competition_category_id' => CompetitionCategory::factory()->active()->create([
                'competition_id' => $team->competition_id,
            ]),
            'user_id' => null,
            'team_id' => $team->id,
        ]);
        $competitionOrgId = Competition::withoutGlobalScope(OrganizationScope::class)
            ->findOrFail($team->competition_id)->organization_id;
        $member = User::factory()->create([
            'organization_id' => $competitionOrgId,
            'role' => UserRole::Participant,
        ]);
        TeamMember::factory()->create([
            'team_id' => $team->id,
            'user_id' => $member->id,
        ]);

        $this->assertTrue($this->policy->view($member, $registration));
    }
}
