<?php

declare(strict_types=1);

namespace Tests\Feature\Competition;

use App\Enums\RegistrationMode;
use App\Models\Competition;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Competition\Concerns\CreatesCompetitionFixtures;
use Tests\TestCase;

class CompetitionRegistrationModeTest extends TestCase
{
    use CreatesCompetitionFixtures;
    use RefreshDatabase;

    public function test_organizer_can_create_competition_with_team_registration_settings(): void
    {
        [$organization, $organizer] = $this->createOrganizerContext();

        $response = $this->actingAs($organizer)->post(route('competitions.store'), [
            'name' => 'Team Hackathon',
            'registration_mode' => RegistrationMode::Team->value,
            'min_team_size' => 2,
            'max_team_size' => 5,
            'requires_coach' => true,
        ]);

        $competition = Competition::query()->where('name', 'Team Hackathon')->first();

        $this->assertNotNull($competition);
        $response->assertRedirect(route('competitions.edit', $competition));
        $this->assertSame(RegistrationMode::Team, $competition->registration_mode);
        $this->assertSame(2, $competition->min_team_size);
        $this->assertSame(5, $competition->max_team_size);
        $this->assertTrue($competition->requires_coach);
    }

    public function test_organizer_can_update_draft_competition_registration_settings(): void
    {
        [$organization, $organizer] = $this->createOrganizerContext();
        $competition = $this->createCompetitionFor($organization);

        $this->actingAs($organizer)->put(route('competitions.update', $competition), [
            'name' => $competition->name,
            'slug' => $competition->slug,
            'registration_mode' => RegistrationMode::Both->value,
            'min_team_size' => 3,
            'max_team_size' => 6,
            'requires_coach' => false,
        ])->assertRedirect(route('competitions.edit', $competition));

        $competition->refresh();

        $this->assertSame(RegistrationMode::Both, $competition->registration_mode);
        $this->assertSame(3, $competition->min_team_size);
        $this->assertSame(6, $competition->max_team_size);
        $this->assertFalse($competition->requires_coach);
    }

    public function test_team_mode_requires_min_and_max_team_size(): void
    {
        [, $organizer] = $this->createOrganizerContext();

        $this->actingAs($organizer)->post(route('competitions.store'), [
            'name' => 'Invalid Team Event',
            'registration_mode' => RegistrationMode::Team->value,
        ])->assertSessionHasErrors(['min_team_size', 'max_team_size']);
    }

    public function test_max_team_size_must_be_greater_than_or_equal_to_min(): void
    {
        [, $organizer] = $this->createOrganizerContext();

        $this->actingAs($organizer)->post(route('competitions.store'), [
            'name' => 'Invalid Sizes',
            'registration_mode' => RegistrationMode::Team->value,
            'min_team_size' => 5,
            'max_team_size' => 2,
        ])->assertSessionHasErrors(['max_team_size']);
    }

    public function test_individual_mode_clears_team_size_fields(): void
    {
        [$organization, $organizer] = $this->createOrganizerContext();
        $competition = Competition::factory()->teamMode()->create([
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($organizer)->put(route('competitions.update', $competition), [
            'name' => $competition->name,
            'slug' => $competition->slug,
            'registration_mode' => RegistrationMode::Individual->value,
        ])->assertRedirect(route('competitions.edit', $competition));

        $competition->refresh();

        $this->assertSame(RegistrationMode::Individual, $competition->registration_mode);
        $this->assertNull($competition->min_team_size);
        $this->assertNull($competition->max_team_size);
        $this->assertFalse($competition->requires_coach);
    }

    public function test_registration_mode_cannot_change_when_teams_exist(): void
    {
        [$organization, $organizer] = $this->createOrganizerContext();
        $competition = Competition::factory()->teamMode()->create([
            'organization_id' => $organization->id,
        ]);

        Team::factory()->create([
            'competition_id' => $competition->id,
        ]);

        $this->actingAs($organizer)->put(route('competitions.update', $competition), [
            'name' => $competition->name,
            'slug' => $competition->slug,
            'registration_mode' => RegistrationMode::Individual->value,
            'min_team_size' => 2,
            'max_team_size' => 5,
        ])->assertSessionHasErrors(['registration_mode']);
    }

    public function test_published_competition_rejects_registration_settings_updates(): void
    {
        [$organization, $organizer] = $this->createOrganizerContext();
        $competition = Competition::factory()->published()->teamMode()->create([
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($organizer)->put(route('competitions.update', $competition), [
            'name' => 'Updated name',
            'registration_mode' => RegistrationMode::Individual->value,
        ])->assertSessionHasErrors(['registration_mode']);
    }

    public function test_published_competition_can_update_non_registration_fields(): void
    {
        [$organization, $organizer] = $this->createOrganizerContext();
        $competition = Competition::factory()->published()->create([
            'organization_id' => $organization->id,
            'name' => 'Original',
        ]);

        $this->actingAs($organizer)->put(route('competitions.update', $competition), [
            'name' => 'Updated published name',
        ])->assertRedirect(route('competitions.edit', $competition));

        $this->assertSame('Updated published name', $competition->fresh()->name);
    }
}
