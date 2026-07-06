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
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_create_category_on_draft_competition(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($organizer)->post(route('competitions.categories.store', $competition), [
            'name' => 'Junior Division',
            'description' => 'Under 18',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Category created.');
        $this->assertDatabaseHas('competition_categories', [
            'competition_id' => $competition->id,
            'name' => 'Junior Division',
            'slug' => 'junior-division',
            'status' => CategoryStatus::Draft->value,
            'is_default' => false,
        ]);
    }

    public function test_organizer_can_update_category(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);
        $category = CompetitionCategory::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Open',
            'slug' => 'open',
        ]);

        $response = $this->actingAs($organizer)->put(
            route('competitions.categories.update', [$competition, $category]),
            [
                'name' => 'Open Track',
                'slug' => 'open-track',
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Category updated.');
        $this->assertDatabaseHas('competition_categories', [
            'id' => $category->id,
            'name' => 'Open Track',
            'slug' => 'open-track',
        ]);
    }

    public function test_organizer_cannot_delete_default_category(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);
        $defaultCategory = CompetitionCategory::factory()->defaultGeneral()->create([
            'competition_id' => $competition->id,
        ]);

        $this->actingAs($organizer)
            ->delete(route('competitions.categories.destroy', [$competition, $defaultCategory]))
            ->assertForbidden();

        $this->assertDatabaseHas('competition_categories', ['id' => $defaultCategory->id, 'deleted_at' => null]);
    }

    public function test_organizer_can_delete_non_default_category(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);
        $category = CompetitionCategory::factory()->create(['competition_id' => $competition->id]);

        $this->actingAs($organizer)
            ->delete(route('competitions.categories.destroy', [$competition, $category]))
            ->assertRedirect()
            ->assertSessionHas('success', 'Category deleted.');

        $this->assertSoftDeleted('competition_categories', ['id' => $category->id]);
    }

    public function test_organizer_can_activate_category_when_competition_is_published(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $category = CompetitionCategory::factory()->create([
            'competition_id' => $competition->id,
            'status' => CategoryStatus::Draft,
        ]);

        $this->actingAs($organizer)
            ->patch(route('competitions.categories.activate', [$competition, $category]))
            ->assertRedirect()
            ->assertSessionHas('success', 'Category activated.');

        $this->assertDatabaseHas('competition_categories', [
            'id' => $category->id,
            'status' => CategoryStatus::Active->value,
        ]);
    }

    public function test_organizer_cannot_activate_category_when_competition_is_draft(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->create([
            'organization_id' => $organization->id,
            'status' => CompetitionStatus::Draft,
        ]);
        $category = CompetitionCategory::factory()->create(['competition_id' => $competition->id]);

        $this->actingAs($organizer)
            ->patch(route('competitions.categories.activate', [$competition, $category]))
            ->assertForbidden();
    }

    public function test_organizer_can_disable_active_category(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $category = CompetitionCategory::factory()->active()->create(['competition_id' => $competition->id]);

        $this->actingAs($organizer)
            ->patch(route('competitions.categories.disable', [$competition, $category]))
            ->assertRedirect()
            ->assertSessionHas('success', 'Category disabled.');

        $this->assertDatabaseHas('competition_categories', [
            'id' => $category->id,
            'status' => CategoryStatus::Disabled->value,
        ]);
    }

    public function test_category_from_other_competition_returns_not_found(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);
        $otherCompetition = Competition::factory()->create(['organization_id' => $organization->id]);
        $category = CompetitionCategory::factory()->create(['competition_id' => $otherCompetition->id]);

        $this->actingAs($organizer)
            ->put(route('competitions.categories.update', [$competition, $category]), [
                'name' => 'Hijacked',
                'slug' => 'hijacked',
            ])
            ->assertNotFound();
    }

    public function test_participant_cannot_manage_categories(): void
    {
        $organization = Organization::factory()->create();
        $participant = User::factory()->create([
            'role' => UserRole::Participant,
            'organization_id' => $organization->id,
        ]);
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($participant)
            ->post(route('competitions.categories.store', $competition), ['name' => 'Blocked'])
            ->assertForbidden();
    }

    public function test_edit_page_includes_categories_with_permissions(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);
        CompetitionCategory::factory()->defaultGeneral()->create(['competition_id' => $competition->id]);

        $this->actingAs($organizer)
            ->get(route('competitions.edit', $competition))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('competition/competitions/Edit')
                ->has('categories', 1)
                ->where('can.createCategory', true)
                ->has('categories.0.can.update')
                ->has('categories.0.can.delete')
            );
    }
}
