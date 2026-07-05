<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Competition;

use App\Enums\CategoryStatus;
use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\Organization;
use App\Models\User;
use App\Services\Competition\CreateCompetitionService;
use App\Services\Competition\DeleteCompetitionService;
use App\Services\Competition\ListCompetitionsService;
use App\Services\Competition\UpdateCompetitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionCrudServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_competition_service_creates_draft_with_default_general_category(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $service = new CreateCompetitionService;

        $competition = $service->execute($organizer, [
            'name' => 'Hackathon 2026',
        ]);

        $this->assertSame('Hackathon 2026', $competition->name);
        $this->assertSame('hackathon-2026', $competition->slug);
        $this->assertSame(CompetitionStatus::Draft, $competition->status);
        $this->assertSame($organization->id, $competition->organization_id);
        $this->assertCount(1, $competition->categories);
        $this->assertTrue($competition->categories->first()->isDefault());
        $this->assertSame('general', $competition->categories->first()->slug);
        $this->assertSame(CategoryStatus::Draft, $competition->categories->first()->status);
    }

    public function test_create_competition_service_generates_unique_slug_when_taken(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        Competition::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Summer Event',
            'slug' => 'summer-event',
        ]);

        $service = new CreateCompetitionService;
        $competition = $service->execute($organizer, [
            'name' => 'Summer Event',
        ]);

        $this->assertSame('summer-event-2', $competition->slug);
    }

    public function test_list_competitions_service_scopes_to_actor_organization(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);

        Competition::factory()->create(['organization_id' => $organization->id, 'name' => 'Mine']);
        Competition::factory()->create(['organization_id' => $otherOrganization->id, 'name' => 'Theirs']);

        $paginator = (new ListCompetitionsService)->execute($organizer);

        $this->assertCount(1, $paginator->items());
        $this->assertSame('Mine', $paginator->items()[0]->name);
    }

    public function test_update_competition_service_updates_fields(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Old Name',
            'slug' => 'old-name',
        ]);

        $updated = (new UpdateCompetitionService)->execute($competition, [
            'name' => 'New Name',
            'slug' => 'new-name',
            'description' => 'Updated description',
            'starts_at' => null,
            'ends_at' => null,
            'registration_starts_at' => null,
            'registration_ends_at' => null,
            'max_participants' => 100,
        ]);

        $this->assertSame('New Name', $updated->name);
        $this->assertSame('new-name', $updated->slug);
        $this->assertSame('Updated description', $updated->description);
        $this->assertSame(100, $updated->max_participants);
    }

    public function test_delete_competition_service_soft_deletes_competition(): void
    {
        $competition = Competition::factory()->create();

        (new DeleteCompetitionService)->execute($competition);

        $this->assertSoftDeleted('competitions', ['id' => $competition->id]);
    }
}
