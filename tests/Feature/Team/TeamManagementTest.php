<?php

declare(strict_types=1);

namespace Tests\Feature\Team;

use App\Enums\RegistrationMode;
use App\Enums\TeamStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_create_and_view_team(): void
    {
        $organization = Organization::factory()->create();
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($participant)
            ->get(route('competitions.teams.index', $competition))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('team/teams/Index')
                ->where('can.create', true));

        $this->actingAs($participant)
            ->get(route('competitions.teams.create', $competition))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('team/teams/Create'));

        $this->actingAs($participant)
            ->post(route('competitions.teams.store', $competition), [
                'name' => 'Code Crushers',
            ])
            ->assertRedirect();

        $team = Team::withoutGlobalScopes()->where('name', 'Code Crushers')->firstOrFail();

        $this->actingAs($participant)
            ->get(route('teams.show', $team))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('team/teams/Show')
                ->where('team.name', 'Code Crushers')
                ->where('can.update', true));
    }

    public function test_participant_can_update_team_name(): void
    {
        $team = Team::factory()->create(['status' => TeamStatus::Forming]);
        $captain = User::query()->findOrFail($team->captain_user_id);

        $this->actingAs($captain)
            ->put(route('teams.update', $team), ['name' => 'New Name'])
            ->assertRedirect(route('teams.show', $team));

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'New Name',
        ]);
    }

    public function test_participant_can_delete_forming_team(): void
    {
        $team = Team::factory()->create(['status' => TeamStatus::Forming]);
        $captain = User::query()->findOrFail($team->captain_user_id);

        $this->actingAs($captain)
            ->delete(route('teams.destroy', $team))
            ->assertRedirect(route('competitions.teams.index', $team->competition_id));

        $this->assertSoftDeleted('teams', ['id' => $team->id]);
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

        $this->actingAs($participant)
            ->get(route('competitions.teams.create', $competition))
            ->assertForbidden();

        $this->actingAs($participant)
            ->post(route('competitions.teams.store', $competition), ['name' => 'Nope'])
            ->assertForbidden();
    }

    public function test_organizer_can_list_all_teams_in_competition(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
        ]);

        Team::factory()->count(2)->create(['competition_id' => $competition->id]);

        $this->actingAs($organizer)
            ->get(route('competitions.teams.index', $competition))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('teams.data', 2)
                ->where('can.create', false));
    }

    public function test_cross_org_participant_cannot_view_team(): void
    {
        $team = Team::factory()->create();
        $outsider = User::factory()->create(['role' => UserRole::Participant]);

        $this->actingAs($outsider)
            ->get(route('teams.show', $team))
            ->assertNotFound();
    }
}
