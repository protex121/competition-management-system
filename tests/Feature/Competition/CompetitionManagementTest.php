<?php

declare(strict_types=1);

namespace Tests\Feature\Competition;

use App\Enums\CategoryStatus;
use App\Enums\CompetitionStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Competition\Concerns\CreatesCompetitionFixtures;
use Tests\TestCase;

class CompetitionManagementTest extends TestCase
{
    use CreatesCompetitionFixtures;
    use RefreshDatabase;

    // --- Access control ---

    public function test_participant_cannot_access_competition_management(): void
    {
        $participant = User::factory()->create(['role' => UserRole::Participant]);

        $this->actingAs($participant)
            ->get(route('competitions.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_view_competition_from_any_organization(): void
    {
        $organization = Organization::factory()->create();
        $competition = $this->createCompetitionFor($organization);
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('competitions.edit', $competition))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('competition/competitions/Edit')
                ->where('competition.id', $competition->id)
            );
    }

    // --- Organizer pages ---

    public function test_organizer_can_view_competition_pages(): void
    {
        [$organization, $organizer] = $this->createOrganizerContext();
        $competition = $this->createCompetitionFor($organization);

        $this->actingAs($organizer)
            ->get(route('competitions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('competition/competitions/Index')
            );

        $this->actingAs($organizer)
            ->get(route('competitions.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('competition/competitions/Create')
            );

        $this->actingAs($organizer)
            ->get(route('competitions.edit', $competition))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('competition/competitions/Edit')
                ->where('competition.id', $competition->id)
                ->has('can.publish')
                ->has('can.activate')
                ->has('can.close')
            );
    }

    // --- CRUD ---

    public function test_organizer_can_create_a_competition_with_default_category(): void
    {
        [$organization, $organizer] = $this->createOrganizerContext();

        $response = $this->actingAs($organizer)->post(route('competitions.store'), [
            'name' => 'Winter Hackathon',
            'description' => 'Annual winter event',
        ]);

        $competition = Competition::query()->where('name', 'Winter Hackathon')->first();

        $this->assertNotNull($competition);
        $response->assertRedirect(route('competitions.edit', $competition));
        $this->assertSame(CompetitionStatus::Draft, $competition->status);
        $this->assertDatabaseHas('competition_categories', [
            'competition_id' => $competition->id,
            'slug' => 'general',
            'is_default' => true,
            'status' => CategoryStatus::Draft->value,
        ]);
    }

    public function test_create_competition_generates_unique_slug_when_name_collides(): void
    {
        [$organization, $organizer] = $this->createOrganizerContext();
        $this->createCompetitionFor($organization, [
            'name' => 'Summer Event',
            'slug' => 'summer-event',
        ]);

        $this->actingAs($organizer)->post(route('competitions.store'), [
            'name' => 'Summer Event',
        ]);

        $this->assertDatabaseHas('competitions', [
            'organization_id' => $organization->id,
            'slug' => 'summer-event-2',
        ]);
    }

    public function test_organizer_can_update_draft_competition(): void
    {
        [$organization, $organizer] = $this->createOrganizerContext();
        $competition = $this->createCompetitionFor($organization, [
            'name' => 'Original Name',
            'slug' => 'original-name',
        ]);

        $this->actingAs($organizer)
            ->put(route('competitions.update', $competition), [
                'name' => 'Updated Name',
                'slug' => 'updated-name',
                'description' => 'New description',
            ])
            ->assertRedirect(route('competitions.edit', $competition));

        $this->assertDatabaseHas('competitions', [
            'id' => $competition->id,
            'name' => 'Updated Name',
            'slug' => 'updated-name',
            'description' => 'New description',
        ]);
    }

    public function test_organizer_can_delete_draft_competition(): void
    {
        [$organization, $organizer] = $this->createOrganizerContext();
        $competition = $this->createCompetitionFor($organization);

        $this->actingAs($organizer)
            ->delete(route('competitions.destroy', $competition))
            ->assertRedirect(route('competitions.index'));

        $this->assertSoftDeleted('competitions', ['id' => $competition->id]);
    }

    public function test_organizer_cannot_delete_published_competition(): void
    {
        [$organization, $organizer] = $this->createOrganizerContext();
        $competition = $this->createCompetitionFor($organization, ['status' => CompetitionStatus::Published]);

        $this->actingAs($organizer)
            ->delete(route('competitions.destroy', $competition))
            ->assertForbidden();

        $this->assertDatabaseHas('competitions', ['id' => $competition->id, 'deleted_at' => null]);
    }

    // --- Lifecycle ---

    public function test_organizer_can_publish_a_draft_competition(): void
    {
        [$organization, $organizer] = $this->createOrganizerContext();
        $competition = $this->createCompetitionFor($organization);

        $this->actingAs($organizer)
            ->patch(route('competitions.publish', $competition))
            ->assertRedirect(route('competitions.edit', $competition))
            ->assertSessionHas('success');

        $this->assertSame(CompetitionStatus::Published, $competition->fresh()->status);
    }

    public function test_organizer_cannot_publish_already_published_competition(): void
    {
        [$organization, $organizer] = $this->createOrganizerContext();
        $competition = $this->createCompetitionFor($organization, ['status' => CompetitionStatus::Published]);

        $this->actingAs($organizer)
            ->patch(route('competitions.publish', $competition))
            ->assertForbidden();
    }

    public function test_organizer_can_activate_published_competition(): void
    {
        [$organization, $organizer] = $this->createOrganizerContext();
        $competition = $this->createCompetitionFor($organization, ['status' => CompetitionStatus::Published]);

        $this->actingAs($organizer)
            ->patch(route('competitions.activate', $competition))
            ->assertRedirect(route('competitions.edit', $competition))
            ->assertSessionHas('success');

        $this->assertSame(CompetitionStatus::Active, $competition->fresh()->status);
    }

    public function test_organizer_cannot_activate_draft_competition(): void
    {
        [$organization, $organizer] = $this->createOrganizerContext();
        $competition = $this->createCompetitionFor($organization);

        $this->actingAs($organizer)
            ->patch(route('competitions.activate', $competition))
            ->assertForbidden();
    }

    public function test_organizer_can_close_active_competition_and_archive_categories(): void
    {
        [$organization, $organizer] = $this->createOrganizerContext();
        $competition = $this->createCompetitionFor($organization, ['status' => CompetitionStatus::Active]);
        $category = CompetitionCategory::factory()->active()->create(['competition_id' => $competition->id]);

        $this->actingAs($organizer)
            ->patch(route('competitions.close', $competition))
            ->assertRedirect(route('competitions.edit', $competition))
            ->assertSessionHas('success');

        $this->assertSame(CompetitionStatus::Closed, $competition->fresh()->status);
        $this->assertDatabaseHas('competition_categories', [
            'id' => $category->id,
            'status' => CategoryStatus::Archived->value,
        ]);
    }

    public function test_organizer_can_close_published_competition(): void
    {
        [$organization, $organizer] = $this->createOrganizerContext();
        $competition = $this->createCompetitionFor($organization, ['status' => CompetitionStatus::Published]);

        $this->actingAs($organizer)
            ->patch(route('competitions.close', $competition))
            ->assertRedirect(route('competitions.edit', $competition));

        $this->assertSame(CompetitionStatus::Closed, $competition->fresh()->status);
    }

    // --- Tenant isolation ---

    public function test_organizer_cannot_access_competition_from_another_organization(): void
    {
        $organizer = User::factory()->organizer()->create();
        $competition = Competition::factory()->create();

        $this->actingAs($organizer)
            ->get(route('competitions.edit', $competition))
            ->assertNotFound();
    }

    public function test_organizer_cannot_update_competition_from_another_organization(): void
    {
        $organizer = User::factory()->organizer()->create();
        $competition = Competition::factory()->create(['name' => 'Other Org Event']);

        $this->actingAs($organizer)
            ->put(route('competitions.update', $competition), [
                'name' => 'Hijacked',
                'slug' => 'hijacked',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('competitions', [
            'id' => $competition->id,
            'name' => 'Other Org Event',
        ]);
    }

    public function test_organizer_cannot_delete_competition_from_another_organization(): void
    {
        $organizer = User::factory()->organizer()->create();
        $competition = Competition::factory()->create();

        $this->actingAs($organizer)
            ->delete(route('competitions.destroy', $competition))
            ->assertNotFound();

        $this->assertDatabaseHas('competitions', ['id' => $competition->id, 'deleted_at' => null]);
    }

    public function test_organizer_cannot_publish_competition_from_another_organization(): void
    {
        $organizer = User::factory()->organizer()->create();
        $competition = Competition::factory()->create();

        $this->actingAs($organizer)
            ->patch(route('competitions.publish', $competition))
            ->assertNotFound();

        $this->assertSame(CompetitionStatus::Draft, $competition->fresh()->status);
    }
}
