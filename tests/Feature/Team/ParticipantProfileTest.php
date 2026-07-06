<?php

declare(strict_types=1);

namespace Tests\Feature\Team;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\ParticipantProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_view_and_update_profile(): void
    {
        $organization = Organization::factory()->create();
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);

        $this->actingAs($participant)
            ->get(route('participant.profile.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('team/profile/Edit'));

        $this->actingAs($participant)
            ->put(route('participant.profile.update'), [
                'bio' => 'Team player',
                'institution' => 'Tech U',
            ])
            ->assertRedirect(route('participant.profile.edit'));

        $this->assertDatabaseHas('participant_profiles', [
            'user_id' => $participant->id,
            'bio' => 'Team player',
            'institution' => 'Tech U',
        ]);
    }

    public function test_organizer_cannot_access_participant_profile_routes(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get(route('participant.profile.edit'))
            ->assertForbidden();
    }

    public function test_participant_can_update_existing_profile(): void
    {
        $participant = User::factory()->create(['role' => UserRole::Participant]);
        ParticipantProfile::factory()->create([
            'user_id' => $participant->id,
            'bio' => 'Old',
        ]);

        $this->actingAs($participant)
            ->put(route('participant.profile.update'), [
                'bio' => 'New bio',
            ])
            ->assertRedirect(route('participant.profile.edit'));

        $this->assertDatabaseHas('participant_profiles', [
            'user_id' => $participant->id,
            'bio' => 'New bio',
        ]);
    }
}
