<?php

declare(strict_types=1);

namespace Tests\Feature\Competition;

use App\Enums\CategoryStatus;
use App\Enums\CompetitionStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_cannot_access_competition_management(): void
    {
        $participant = User::factory()->create(['role' => UserRole::Participant]);

        $this->actingAs($participant)
            ->get(route('competitions.index'))
            ->assertForbidden();
    }

    public function test_organizer_can_view_competition_pages(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);

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

    public function test_organizer_can_create_a_competition_with_default_category(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);

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

    public function test_organizer_can_publish_a_draft_competition(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($organizer)
            ->patch(route('competitions.publish', $competition))
            ->assertRedirect(route('competitions.edit', $competition))
            ->assertSessionHas('success');

        $this->assertSame(CompetitionStatus::Published, $competition->fresh()->status);
    }

    public function test_organizer_cannot_access_competition_from_another_organization(): void
    {
        $organizer = User::factory()->organizer()->create();
        $competition = Competition::factory()->create();

        $this->actingAs($organizer)
            ->get(route('competitions.edit', $competition))
            ->assertNotFound();
    }
}
