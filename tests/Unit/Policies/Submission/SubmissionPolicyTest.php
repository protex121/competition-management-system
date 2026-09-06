<?php

declare(strict_types=1);

namespace Tests\Unit\Policies\Submission;

use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Scopes\OrganizationScope;
use App\Models\Submission;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Policies\Submission\SubmissionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionPolicyTest extends TestCase
{
    use RefreshDatabase;

    private SubmissionPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new SubmissionPolicy;
    }

    public function test_owner_can_manage_confirmed_individual_registration(): void
    {
        $competition = Competition::factory()->published()->create();
        $category = CompetitionCategory::factory()->active()->create(['competition_id' => $competition->id]);
        $registration = Registration::factory()->create(['competition_category_id' => $category->id]);
        $owner = User::query()->findOrFail($registration->user_id);

        $this->assertTrue($this->policy->manage($owner, $registration));
    }

    public function test_cannot_manage_withdrawn_registration(): void
    {
        $registration = Registration::factory()->withdrawn()->create();
        $owner = User::query()->findOrFail($registration->user_id);

        $this->assertFalse($this->policy->manage($owner, $registration));
    }

    public function test_non_owner_cannot_manage_individual_registration(): void
    {
        $registration = Registration::factory()->create();
        $other = User::factory()->create(['role' => UserRole::Participant]);

        $this->assertFalse($this->policy->manage($other, $registration));
    }

    public function test_any_active_team_member_can_manage_team_registration_submission(): void
    {
        $competition = Competition::factory()->teamMode()->published()->create();
        $team = Team::factory()->approved()->create(['competition_id' => $competition->id]);
        $category = CompetitionCategory::factory()->active()->create(['competition_id' => $competition->id]);
        $registration = Registration::factory()->create([
            'competition_category_id' => $category->id,
            'user_id' => null,
            'team_id' => $team->id,
        ]);
        $organizationId = Competition::withoutGlobalScope(OrganizationScope::class)
            ->findOrFail($team->competition_id)->organization_id;
        $member = User::factory()->create([
            'organization_id' => $organizationId,
            'role' => UserRole::Participant,
        ]);
        TeamMember::factory()->create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => TeamMemberRole::Member,
            'status' => TeamMemberStatus::Active,
        ]);

        $this->assertTrue($this->policy->manage($member, $registration));
    }

    public function test_non_member_cannot_manage_team_registration_submission(): void
    {
        $team = Team::factory()->approved()->create();
        $registration = Registration::factory()->create([
            'user_id' => null,
            'team_id' => $team->id,
        ]);
        $other = User::factory()->create(['role' => UserRole::Participant]);

        $this->assertFalse($this->policy->manage($other, $registration));
    }

    public function test_owner_can_update_draft_submission(): void
    {
        $registration = Registration::factory()->create();
        $submission = Submission::factory()->create(['registration_id' => $registration->id]);
        $owner = User::query()->findOrFail($registration->user_id);

        $this->assertTrue($this->policy->update($owner, $submission));
        $this->assertTrue($this->policy->finalize($owner, $submission));
    }

    public function test_cannot_update_finalized_submission(): void
    {
        $registration = Registration::factory()->create();
        $submission = Submission::factory()->finalized()->create(['registration_id' => $registration->id]);
        $owner = User::query()->findOrFail($registration->user_id);

        $this->assertFalse($this->policy->update($owner, $submission));
        $this->assertFalse($this->policy->finalize($owner, $submission));
    }

    public function test_owner_can_view_own_submission_regardless_of_status(): void
    {
        $registration = Registration::factory()->create();
        $submission = Submission::factory()->finalized()->create(['registration_id' => $registration->id]);
        $owner = User::query()->findOrFail($registration->user_id);

        $this->assertTrue($this->policy->view($owner, $submission));
    }

    public function test_organizer_can_view_submission_in_their_org(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $category = CompetitionCategory::factory()->active()->create(['competition_id' => $competition->id]);
        $registration = Registration::factory()->create(['competition_category_id' => $category->id]);
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $submission = Submission::factory()->create(['registration_id' => $registration->id]);

        $this->assertTrue($this->policy->view($organizer, $submission));
    }

    public function test_organizer_from_other_org_cannot_view_submission(): void
    {
        $registration = Registration::factory()->create();
        $submission = Submission::factory()->create(['registration_id' => $registration->id]);
        $organizer = User::factory()->organizer()->create();

        $this->assertFalse($this->policy->view($organizer, $submission));
    }

    public function test_view_any_true_for_organizer_false_for_participant(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);

        $this->assertTrue($this->policy->viewAny($organizer, $competition));
        $this->assertFalse($this->policy->viewAny($participant, $competition));
    }
}
