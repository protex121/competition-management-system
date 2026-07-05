<?php

declare(strict_types=1);

namespace Tests\Unit\Policies\Identity;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use App\Policies\Identity\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    private UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new UserPolicy;
    }

    public function test_organizer_and_super_admin_can_view_user_lists(): void
    {
        $organizer = User::factory()->organizer()->create();
        $superAdmin = User::factory()->superAdmin()->create();
        $participant = User::factory()->create();

        $this->assertTrue($this->policy->viewAny($organizer));
        $this->assertTrue($this->policy->viewAny($superAdmin));
        $this->assertFalse($this->policy->viewAny($participant));
    }

    public function test_users_can_view_themselves(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->view($user, $user));
    }

    public function test_organizer_can_view_users_in_the_same_organization(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $member = User::factory()->create(['organization_id' => $organization->id]);

        $this->assertTrue($this->policy->view($organizer, $member));
    }

    public function test_organizer_cannot_view_users_from_another_organization(): void
    {
        $organizer = User::factory()->organizer()->create();
        $outsider = User::factory()->create();

        $this->assertFalse($this->policy->view($organizer, $outsider));
    }

    public function test_organizer_can_create_users(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->assertTrue($this->policy->create($organizer));
    }

    public function test_participant_cannot_create_users(): void
    {
        $participant = User::factory()->create(['role' => UserRole::Participant]);

        $this->assertFalse($this->policy->create($participant));
    }

    public function test_organizer_can_update_other_users_in_their_organization(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $member = User::factory()->create(['organization_id' => $organization->id]);

        $this->assertTrue($this->policy->update($organizer, $member));
    }

    public function test_organizer_cannot_update_themselves_via_user_management(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->assertFalse($this->policy->update($organizer, $organizer));
    }

    public function test_organizer_cannot_update_super_admin(): void
    {
        $organizer = User::factory()->organizer()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $this->assertFalse($this->policy->update($organizer, $superAdmin));
    }

    public function test_organizer_cannot_delete_themselves(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->assertFalse($this->policy->delete($organizer, $organizer));
        $this->assertFalse($this->policy->deactivate($organizer, $organizer));
    }

    public function test_organizer_can_reactivate_deactivated_users_in_their_organization(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $member = User::factory()->deactivated()->create(['organization_id' => $organization->id]);

        $this->assertTrue($this->policy->reactivate($organizer, $member));
    }

    public function test_organizer_cannot_reactivate_active_users(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $member = User::factory()->create(['organization_id' => $organization->id]);

        $this->assertFalse($this->policy->reactivate($organizer, $member));
    }

    public function test_organizer_cannot_deactivate_already_deactivated_users(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $member = User::factory()->deactivated()->create(['organization_id' => $organization->id]);

        $this->assertFalse($this->policy->deactivate($organizer, $member));
    }

    public function test_last_organizer_in_an_organization_cannot_be_deleted(): void
    {
        $organization = Organization::factory()->create();
        $soleOrganizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $superAdmin = User::factory()->superAdmin()->create();

        $this->assertFalse($this->policy->delete($superAdmin, $soleOrganizer));
    }

    public function test_organizer_can_delete_another_organizer_when_more_than_one_exists(): void
    {
        $organization = Organization::factory()->create();
        $firstOrganizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $secondOrganizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);

        $this->assertTrue($this->policy->delete($firstOrganizer, $secondOrganizer));
    }

    public function test_organizer_can_delete_participants_in_their_organization(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);

        $this->assertTrue($this->policy->delete($organizer, $participant));
    }

    public function test_super_admin_can_manage_users_across_organizations(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $member = User::factory()->create();

        $this->assertTrue($this->policy->view($superAdmin, $member));
        $this->assertTrue($this->policy->update($superAdmin, $member));
        $this->assertTrue($this->policy->delete($superAdmin, $member));
        $this->assertTrue($this->policy->restore($superAdmin, $member));
    }
}
