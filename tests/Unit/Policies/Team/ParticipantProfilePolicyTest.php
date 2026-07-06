<?php

declare(strict_types=1);

namespace Tests\Unit\Policies\Team;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\ParticipantProfile;
use App\Models\User;
use App\Policies\Team\ParticipantProfilePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantProfilePolicyTest extends TestCase
{
    use RefreshDatabase;

    private ParticipantProfilePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ParticipantProfilePolicy;
    }

    public function test_participant_can_view_and_update_own_profile(): void
    {
        $participant = User::factory()->create(['role' => UserRole::Participant]);
        $profile = ParticipantProfile::factory()->create(['user_id' => $participant->id]);

        $this->assertTrue($this->policy->view($participant, $profile));
        $this->assertTrue($this->policy->update($participant, $profile));
    }

    public function test_organizer_can_view_profiles_in_their_organization(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);
        $profile = ParticipantProfile::factory()->create(['user_id' => $participant->id]);

        $this->assertTrue($this->policy->view($organizer, $profile));
        $this->assertFalse($this->policy->update($organizer, $profile));
    }

    public function test_organizer_cannot_view_profiles_from_another_organization(): void
    {
        $organizer = User::factory()->organizer()->create();
        $profile = ParticipantProfile::factory()->create();

        $this->assertFalse($this->policy->view($organizer, $profile));
    }

    public function test_participant_can_create_profile(): void
    {
        $participant = User::factory()->create(['role' => UserRole::Participant]);

        $this->assertTrue($this->policy->create($participant));
    }

    public function test_organizer_cannot_create_participant_profile_via_policy(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->assertFalse($this->policy->create($organizer));
    }

    public function test_only_super_admin_can_delete_profiles(): void
    {
        $profile = ParticipantProfile::factory()->create();
        $participant = User::factory()->create(['role' => UserRole::Participant]);
        $superAdmin = User::factory()->superAdmin()->create();

        $this->assertFalse($this->policy->delete($participant, $profile));
        $this->assertTrue($this->policy->delete($superAdmin, $profile));
    }
}
