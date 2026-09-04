<?php

declare(strict_types=1);

namespace Tests\Feature\Registration;

use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Models\Competition;
use App\Models\Organization;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\Registration\RegistrationConfirmed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Registration\Concerns\CreatesRegistrationFixtures;
use Tests\TestCase;

class RegistrationNotificationTest extends TestCase
{
    use CreatesRegistrationFixtures;
    use RefreshDatabase;

    public function test_individual_registration_records_a_confirmation_notification(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $category = $this->createActiveCategoryFor($competition);
        $participant = $this->createParticipantFor($organization);

        $this->actingAs($participant)
            ->post(route('competitions.registrations.store', $competition), [
                'competition_category_id' => $category->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $participant->id,
            'notifiable_type' => User::class,
            'type' => RegistrationConfirmed::class,
        ]);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_team_registration_records_a_confirmation_notification_for_every_active_member(): void
    {
        $competition = Competition::factory()->teamMode()->published()->create(['min_team_size' => 1]);
        $category = $this->createActiveCategoryFor($competition);
        $team = Team::factory()->approved()->create(['competition_id' => $competition->id]);
        $captain = User::query()->findOrFail($team->captain_user_id);

        $organizationId = Competition::withoutGlobalScopes()->findOrFail($team->competition_id)->organization_id;
        $secondMember = $this->createParticipantFor(Organization::query()->findOrFail($organizationId));
        TeamMember::factory()->create([
            'team_id' => $team->id,
            'user_id' => $secondMember->id,
            'role' => TeamMemberRole::Member,
            'status' => TeamMemberStatus::Active,
        ]);
        $removedMember = $this->createParticipantFor(Organization::query()->findOrFail($organizationId));
        TeamMember::factory()->removed()->create([
            'team_id' => $team->id,
            'user_id' => $removedMember->id,
        ]);

        $this->actingAs($captain)
            ->post(route('teams.registrations.store', $team), [
                'competition_category_id' => $category->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $captain->id,
            'type' => RegistrationConfirmed::class,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $secondMember->id,
            'type' => RegistrationConfirmed::class,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $removedMember->id,
            'type' => RegistrationConfirmed::class,
        ]);
        $this->assertDatabaseCount('notifications', 2);
    }
}
