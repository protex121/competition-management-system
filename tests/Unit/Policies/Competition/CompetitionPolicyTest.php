<?php

declare(strict_types=1);

namespace Tests\Unit\Policies\Competition;

use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Organization;
use App\Models\User;
use App\Policies\Competition\CompetitionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionPolicyTest extends TestCase
{
    use RefreshDatabase;

    private CompetitionPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new CompetitionPolicy;
    }

    public function test_organizer_and_super_admin_can_view_competition_lists(): void
    {
        $organizer = User::factory()->organizer()->create();
        $superAdmin = User::factory()->superAdmin()->create();
        $participant = User::factory()->create(['role' => UserRole::Participant]);

        $this->assertTrue($this->policy->viewAny($organizer));
        $this->assertTrue($this->policy->viewAny($superAdmin));
        $this->assertFalse($this->policy->viewAny($participant));
    }

    public function test_organizer_can_view_competitions_in_their_organization(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);

        $this->assertTrue($this->policy->view($organizer, $competition));
    }

    public function test_organizer_cannot_view_competitions_from_another_organization(): void
    {
        $organizer = User::factory()->organizer()->create();
        $competition = Competition::factory()->create();

        $this->assertFalse($this->policy->view($organizer, $competition));
    }

    public function test_participant_can_view_published_active_and_closed_competitions(): void
    {
        $participant = User::factory()->create(['role' => UserRole::Participant]);
        $organization = Organization::factory()->create();

        $published = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $active = Competition::factory()->active()->create(['organization_id' => $organization->id]);
        $closed = Competition::factory()->closed()->create(['organization_id' => $organization->id]);

        $this->assertTrue($this->policy->view($participant, $published));
        $this->assertTrue($this->policy->view($participant, $active));
        $this->assertTrue($this->policy->view($participant, $closed));
    }

    public function test_participant_cannot_view_draft_competitions(): void
    {
        $participant = User::factory()->create(['role' => UserRole::Participant]);
        $competition = Competition::factory()->create();

        $this->assertFalse($this->policy->view($participant, $competition));
    }

    public function test_organizer_and_super_admin_can_create_competitions(): void
    {
        $organizer = User::factory()->organizer()->create();
        $superAdmin = User::factory()->superAdmin()->create();
        $participant = User::factory()->create(['role' => UserRole::Participant]);

        $this->assertTrue($this->policy->create($organizer));
        $this->assertTrue($this->policy->create($superAdmin));
        $this->assertFalse($this->policy->create($participant));
    }

    public function test_organizer_can_update_competitions_in_their_organization_when_not_closed(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $draft = Competition::factory()->create(['organization_id' => $organization->id]);
        $published = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $active = Competition::factory()->active()->create(['organization_id' => $organization->id]);

        $this->assertTrue($this->policy->update($organizer, $draft));
        $this->assertTrue($this->policy->update($organizer, $published));
        $this->assertTrue($this->policy->update($organizer, $active));
    }

    public function test_organizer_cannot_update_closed_competitions(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $closed = Competition::factory()->closed()->create(['organization_id' => $organization->id]);

        $this->assertFalse($this->policy->update($organizer, $closed));
    }

    public function test_organizer_cannot_update_competitions_from_another_organization(): void
    {
        $organizer = User::factory()->organizer()->create();
        $competition = Competition::factory()->create();

        $this->assertFalse($this->policy->update($organizer, $competition));
    }

    public function test_only_draft_competitions_can_be_deleted(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $draft = Competition::factory()->create(['organization_id' => $organization->id]);
        $published = Competition::factory()->published()->create(['organization_id' => $organization->id]);

        $this->assertTrue($this->policy->delete($organizer, $draft));
        $this->assertFalse($this->policy->delete($organizer, $published));
    }

    public function test_only_draft_competitions_can_be_published(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $draft = Competition::factory()->create(['organization_id' => $organization->id]);
        $published = Competition::factory()->published()->create(['organization_id' => $organization->id]);

        $this->assertTrue($this->policy->publish($organizer, $draft));
        $this->assertFalse($this->policy->publish($organizer, $published));
    }

    public function test_only_published_competitions_can_be_activated(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $draft = Competition::factory()->create(['organization_id' => $organization->id]);
        $published = Competition::factory()->published()->create(['organization_id' => $organization->id]);

        $this->assertFalse($this->policy->activate($organizer, $draft));
        $this->assertTrue($this->policy->activate($organizer, $published));
    }

    public function test_published_and_active_competitions_can_be_closed(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $draft = Competition::factory()->create(['organization_id' => $organization->id]);
        $published = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $active = Competition::factory()->active()->create(['organization_id' => $organization->id]);

        $this->assertFalse($this->policy->close($organizer, $draft));
        $this->assertTrue($this->policy->close($organizer, $published));
        $this->assertTrue($this->policy->close($organizer, $active));
    }

    public function test_super_admin_can_manage_competitions_across_organizations(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $competition = Competition::factory()->create();

        $this->assertTrue($this->policy->view($superAdmin, $competition));
        $this->assertTrue($this->policy->update($superAdmin, $competition));
        $this->assertTrue($this->policy->delete($superAdmin, $competition));
        $this->assertTrue($this->policy->publish($superAdmin, $competition));
    }
}
