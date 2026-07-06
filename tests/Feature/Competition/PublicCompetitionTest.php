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

class PublicCompetitionTest extends TestCase
{
    use CreatesCompetitionFixtures;
    use RefreshDatabase;

    // --- Public visibility ---

    public function test_guest_can_view_published_competition(): void
    {
        $organization = Organization::factory()->create(['slug' => 'acme-corp']);
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
            'slug' => 'winter-hackathon',
            'name' => 'Winter Hackathon',
            'description' => 'Annual event',
        ]);
        CompetitionCategory::factory()->active()->create([
            'competition_id' => $competition->id,
            'name' => 'Junior',
            'slug' => 'junior',
        ]);
        CompetitionCategory::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Draft Track',
            'status' => CategoryStatus::Draft,
        ]);

        $this->get(route('events.competitions.show', [
            'organization' => $organization->slug,
            'competition' => $competition->slug,
        ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('competition/public/Show')
                ->where('competition.name', 'Winter Hackathon')
                ->where('competition.registration_mode', 'team')
                ->where('competition.min_team_size', 2)
                ->where('competition.max_team_size', 5)
                ->where('organization.slug', 'acme-corp')
                ->has('categories', 1)
                ->where('categories.0.name', 'Junior')
            );
    }

    public function test_guest_can_view_active_and_closed_competitions(): void
    {
        $organization = Organization::factory()->create(['slug' => 'acme-corp']);
        $active = Competition::factory()->active()->create([
            'organization_id' => $organization->id,
            'slug' => 'spring-cup',
        ]);
        $closed = Competition::factory()->closed()->create([
            'organization_id' => $organization->id,
            'slug' => 'autumn-finals',
        ]);

        $this->get(route('events.competitions.show', [
            'organization' => $organization->slug,
            'competition' => $active->slug,
        ]))->assertOk();

        $this->get(route('events.competitions.show', [
            'organization' => $organization->slug,
            'competition' => $closed->slug,
        ]))->assertOk();
    }

    public function test_draft_competition_is_not_publicly_accessible(): void
    {
        $organization = Organization::factory()->create(['slug' => 'acme-corp']);
        $competition = Competition::factory()->create([
            'organization_id' => $organization->id,
            'slug' => 'secret-event',
            'status' => CompetitionStatus::Draft,
        ]);

        $this->get(route('events.competitions.show', [
            'organization' => $organization->slug,
            'competition' => $competition->slug,
        ]))->assertNotFound();
    }

    // --- Slug resolution ---

    public function test_unknown_organization_or_competition_returns_not_found(): void
    {
        $organization = Organization::factory()->create(['slug' => 'acme-corp']);
        Competition::factory()->published()->create([
            'organization_id' => $organization->id,
            'slug' => 'winter-hackathon',
        ]);

        $this->get(route('events.competitions.show', [
            'organization' => 'missing-org',
            'competition' => 'winter-hackathon',
        ]))->assertNotFound();

        $this->get(route('events.competitions.show', [
            'organization' => $organization->slug,
            'competition' => 'missing-event',
        ]))->assertNotFound();
    }

    public function test_competition_slug_is_scoped_to_organization(): void
    {
        $orgA = Organization::factory()->create(['slug' => 'org-a']);
        $orgB = Organization::factory()->create(['slug' => 'org-b']);
        Competition::factory()->published()->create([
            'organization_id' => $orgA->id,
            'slug' => 'shared-slug',
        ]);

        $this->get(route('events.competitions.show', [
            'organization' => $orgB->slug,
            'competition' => 'shared-slug',
        ]))->assertNotFound();
    }

    // --- Category display ---

    public function test_closed_competition_shows_archived_categories(): void
    {
        $organization = Organization::factory()->create(['slug' => 'acme-corp']);
        $competition = Competition::factory()->closed()->create([
            'organization_id' => $organization->id,
            'slug' => 'past-event',
        ]);
        CompetitionCategory::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'General',
            'slug' => 'general',
            'status' => CategoryStatus::Archived,
        ]);

        $this->get(route('events.competitions.show', [
            'organization' => $organization->slug,
            'competition' => $competition->slug,
        ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('categories', 1)
                ->where('categories.0.name', 'General')
            );
    }

    public function test_public_page_includes_participation_mode_hints(): void
    {
        $organization = Organization::factory()->create(['slug' => 'acme-corp']);
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
            'slug' => 'team-event',
            'min_team_size' => 2,
            'max_team_size' => 4,
            'requires_coach' => true,
        ]);

        $this->get(route('events.competitions.show', [
            'organization' => $organization->slug,
            'competition' => $competition->slug,
        ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('competition.registration_mode', 'team')
                ->where('competition.min_team_size', 2)
                ->where('competition.max_team_size', 4)
                ->where('competition.requires_coach', true)
            );
    }

    public function test_public_page_shows_guest_participation_cta(): void
    {
        $organization = Organization::factory()->create(['slug' => 'acme-corp']);
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
            'slug' => 'open-event',
        ]);

        $this->get(route('events.competitions.show', [
            'organization' => $organization->slug,
            'competition' => $competition->slug,
        ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('participation.visible', true)
                ->where('participation.status', 'guest')
                ->has('participation.login_url')
                ->has('participation.register_url')
            );
    }

    public function test_public_page_shows_join_team_cta_for_participant(): void
    {
        $organization = Organization::factory()->create(['slug' => 'acme-corp']);
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
            'slug' => 'join-event',
        ]);
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);

        $this->actingAs($participant)
            ->get(route('events.competitions.show', [
                'organization' => $organization->slug,
                'competition' => $competition->slug,
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('participation.status', 'join_team')
                ->has('participation.action_url')
            );
    }
}
