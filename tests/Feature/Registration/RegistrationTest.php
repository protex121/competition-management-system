<?php

declare(strict_types=1);

namespace Tests\Feature\Registration;

use App\Enums\RegistrationStatus;
use App\Models\Competition;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Registration\Concerns\CreatesRegistrationFixtures;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use CreatesRegistrationFixtures;
    use RefreshDatabase;

    public function test_participant_can_register_solo_for_active_category(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $category = $this->createActiveCategoryFor($competition);
        $participant = $this->createParticipantFor($organization);

        $this->actingAs($participant)
            ->post(route('competitions.registrations.store', $competition), [
                'competition_category_id' => $category->id,
            ])
            ->assertRedirect(route('registrations.index'));

        $this->assertDatabaseHas('registrations', [
            'competition_category_id' => $category->id,
            'user_id' => $participant->id,
            'team_id' => null,
            'status' => RegistrationStatus::Confirmed->value,
        ]);
    }

    public function test_captain_can_register_approved_team(): void
    {
        $competition = Competition::factory()->teamMode()->published()->create(['min_team_size' => 1]);
        $category = $this->createActiveCategoryFor($competition);
        $team = Team::factory()->approved()->create(['competition_id' => $competition->id]);
        $captain = User::query()->findOrFail($team->captain_user_id);

        $this->actingAs($captain)
            ->post(route('teams.registrations.store', $team), [
                'competition_category_id' => $category->id,
            ])
            ->assertRedirect(route('teams.show', $team));

        $this->assertDatabaseHas('registrations', [
            'competition_category_id' => $category->id,
            'team_id' => $team->id,
            'user_id' => null,
            'status' => RegistrationStatus::Confirmed->value,
        ]);
    }

    public function test_registration_blocked_after_deadline(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $category = $this->createActiveCategoryFor($competition, [
            'registration_ends_at' => now()->subDay(),
        ]);
        $participant = $this->createParticipantFor($organization);

        $this->actingAs($participant)
            ->post(route('competitions.registrations.store', $competition), [
                'competition_category_id' => $category->id,
            ])
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_registration_blocked_when_category_at_capacity(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
            'min_team_size' => 1,
        ]);
        $category = $this->createActiveCategoryFor($competition, ['max_participants' => 1]);

        $fullTeam = Team::factory()->approved()->create(['competition_id' => $competition->id]);
        Registration::factory()->create([
            'competition_category_id' => $category->id,
            'user_id' => null,
            'team_id' => $fullTeam->id,
        ]);

        $secondTeam = Team::factory()->approved()->create(['competition_id' => $competition->id]);
        $captain = User::query()->findOrFail($secondTeam->captain_user_id);

        $this->actingAs($captain)
            ->post(route('teams.registrations.store', $secondTeam), [
                'competition_category_id' => $category->id,
            ])
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('registrations', 1);
    }

    public function test_duplicate_registration_into_same_category_blocked(): void
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

        $this->actingAs($participant)
            ->post(route('competitions.registrations.store', $competition), [
                'competition_category_id' => $category->id,
            ])
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('registrations', 1);
    }

    public function test_second_active_registration_into_different_category_blocked(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $categoryOne = $this->createActiveCategoryFor($competition);
        $categoryTwo = $this->createActiveCategoryFor($competition);
        $participant = $this->createParticipantFor($organization);

        $this->actingAs($participant)
            ->post(route('competitions.registrations.store', $competition), [
                'competition_category_id' => $categoryOne->id,
            ])
            ->assertRedirect();

        $this->actingAs($participant)
            ->post(route('competitions.registrations.store', $competition), [
                'competition_category_id' => $categoryTwo->id,
            ])
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('registrations', 1);
    }

    public function test_withdraw_frees_capacity_slot(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $category = $this->createActiveCategoryFor($competition, ['max_participants' => 1]);
        $firstParticipant = $this->createParticipantFor($organization);
        $secondParticipant = $this->createParticipantFor($organization);

        $this->actingAs($firstParticipant)
            ->post(route('competitions.registrations.store', $competition), [
                'competition_category_id' => $category->id,
            ])
            ->assertRedirect();

        $registration = Registration::withoutGlobalScopes()->where('user_id', $firstParticipant->id)->firstOrFail();

        $this->actingAs($firstParticipant)
            ->patch(route('registrations.withdraw', $registration))
            ->assertRedirect(route('registrations.index'));

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'status' => RegistrationStatus::Withdrawn->value,
        ]);

        $this->actingAs($secondParticipant)
            ->post(route('competitions.registrations.store', $competition), [
                'competition_category_id' => $category->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('registrations', [
            'competition_category_id' => $category->id,
            'user_id' => $secondParticipant->id,
            'status' => RegistrationStatus::Confirmed->value,
        ]);
    }

    public function test_non_captain_cannot_register_team(): void
    {
        $competition = Competition::factory()->teamMode()->published()->create();
        $category = $this->createActiveCategoryFor($competition);
        $team = Team::factory()->approved()->create(['competition_id' => $competition->id]);
        $organizationId = Competition::withoutGlobalScopes()->findOrFail($team->competition_id)->organization_id;
        $other = $this->createParticipantFor(Organization::query()->findOrFail($organizationId));

        $this->actingAs($other)
            ->post(route('teams.registrations.store', $team), [
                'competition_category_id' => $category->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_cross_org_participant_cannot_register(): void
    {
        $competition = Competition::factory()->published()->create();
        $category = $this->createActiveCategoryFor($competition);
        $outsider = $this->createParticipantFor(Organization::factory()->create());

        $this->actingAs($outsider)
            ->post(route('competitions.registrations.store', $competition), [
                'competition_category_id' => $category->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_unapproved_team_cannot_register(): void
    {
        $competition = Competition::factory()->teamMode()->published()->create();
        $category = $this->createActiveCategoryFor($competition);
        $team = Team::factory()->create(['competition_id' => $competition->id]);
        $captain = User::query()->findOrFail($team->captain_user_id);

        $this->actingAs($captain)
            ->post(route('teams.registrations.store', $team), [
                'competition_category_id' => $category->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_organizer_can_view_registrations_for_a_category(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $category = $this->createActiveCategoryFor($competition);
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $participant = $this->createParticipantFor($organization);

        $this->actingAs($participant)
            ->post(route('competitions.registrations.store', $competition), [
                'competition_category_id' => $category->id,
            ])
            ->assertRedirect();

        $this->actingAs($organizer)
            ->get(route('competitions.categories.registrations.index', [$competition, $category]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('registration/registrations/Review', shouldExist: false)
                ->has('registrations', 1));
    }
}
