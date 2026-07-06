<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Competition;

use App\Enums\CategoryStatus;
use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Organization;
use App\Services\Competition\ShowPublicCompetitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ShowPublicCompetitionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_returns_public_competition_with_active_categories(): void
    {
        $organization = Organization::factory()->create(['slug' => 'acme']);
        $competition = Competition::factory()->published()->create([
            'organization_id' => $organization->id,
            'slug' => 'hackathon',
            'name' => 'Hackathon',
        ]);
        CompetitionCategory::factory()->active()->create([
            'competition_id' => $competition->id,
            'name' => 'Open',
        ]);

        $result = (new ShowPublicCompetitionService)->execute('acme', 'hackathon');

        $this->assertSame('Hackathon', $result['competition']['name']);
        $this->assertSame('acme', $result['organization']['slug']);
        $this->assertArrayHasKey('registration_mode', $result['competition']);
        $this->assertCount(1, $result['categories']);
        $this->assertSame('Open', $result['categories'][0]['name']);
        $this->assertSame('individual', $result['competition']['registration_mode']);
    }

    public function test_service_aborts_for_draft_competition(): void
    {
        $organization = Organization::factory()->create(['slug' => 'acme']);
        Competition::factory()->create([
            'organization_id' => $organization->id,
            'slug' => 'draft-event',
            'status' => CompetitionStatus::Draft,
        ]);

        $this->expectException(NotFoundHttpException::class);

        (new ShowPublicCompetitionService)->execute('acme', 'draft-event');
    }

    public function test_service_returns_archived_categories_for_closed_competition(): void
    {
        $organization = Organization::factory()->create(['slug' => 'acme']);
        $competition = Competition::factory()->closed()->create([
            'organization_id' => $organization->id,
            'slug' => 'closed-event',
        ]);
        CompetitionCategory::factory()->create([
            'competition_id' => $competition->id,
            'status' => CategoryStatus::Archived,
            'name' => 'Finals',
        ]);

        $result = (new ShowPublicCompetitionService)->execute('acme', 'closed-event');

        $this->assertCount(1, $result['categories']);
        $this->assertSame('Finals', $result['categories'][0]['name']);
    }
}
