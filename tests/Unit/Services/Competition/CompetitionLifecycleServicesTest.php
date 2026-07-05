<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Competition;

use App\Enums\CategoryStatus;
use App\Enums\CompetitionStatus;
use App\Events\Competition\CompetitionPublished;
use App\Exceptions\Competition\InvalidCompetitionStatusTransitionException;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Organization;
use App\Models\User;
use App\Services\Competition\ActivateCompetitionService;
use App\Services\Competition\CloseCompetitionService;
use App\Services\Competition\PublishCompetitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CompetitionLifecycleServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_publish_service_transitions_draft_to_published_and_dispatches_event(): void
    {
        Event::fake([CompetitionPublished::class]);

        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);

        $result = (new PublishCompetitionService)->execute($organizer, $competition);

        $this->assertSame(CompetitionStatus::Published, $result->status);
        Event::assertDispatched(CompetitionPublished::class, fn (CompetitionPublished $event): bool => $event->competition->is($competition));
    }

    public function test_publish_service_rejects_non_draft_competitions(): void
    {
        $organizer = User::factory()->organizer()->create();
        $competition = Competition::factory()->published()->create(['organization_id' => $organizer->organization_id]);

        $this->expectException(InvalidCompetitionStatusTransitionException::class);

        (new PublishCompetitionService)->execute($organizer, $competition);
    }

    public function test_activate_service_transitions_published_to_active(): void
    {
        $organizer = User::factory()->organizer()->create();
        $competition = Competition::factory()->published()->create(['organization_id' => $organizer->organization_id]);

        $result = (new ActivateCompetitionService)->execute($organizer, $competition);

        $this->assertSame(CompetitionStatus::Active, $result->status);
    }

    public function test_activate_service_rejects_non_published_competitions(): void
    {
        $organizer = User::factory()->organizer()->create();
        $competition = Competition::factory()->create(['organization_id' => $organizer->organization_id]);

        $this->expectException(InvalidCompetitionStatusTransitionException::class);

        (new ActivateCompetitionService)->execute($organizer, $competition);
    }

    public function test_close_service_transitions_published_competition_and_archives_categories(): void
    {
        $organizer = User::factory()->organizer()->create();
        $competition = Competition::factory()->published()->create(['organization_id' => $organizer->organization_id]);
        $activeCategory = CompetitionCategory::factory()->active()->create(['competition_id' => $competition->id]);
        $draftCategory = CompetitionCategory::factory()->defaultGeneral()->create(['competition_id' => $competition->id]);

        $result = (new CloseCompetitionService)->execute($organizer, $competition);

        $this->assertSame(CompetitionStatus::Closed, $result->status);
        $this->assertSame(CategoryStatus::Archived, $activeCategory->fresh()->status);
        $this->assertSame(CategoryStatus::Archived, $draftCategory->fresh()->status);
    }

    public function test_close_service_accepts_active_competitions(): void
    {
        $organizer = User::factory()->organizer()->create();
        $competition = Competition::factory()->active()->create(['organization_id' => $organizer->organization_id]);

        $result = (new CloseCompetitionService)->execute($organizer, $competition);

        $this->assertSame(CompetitionStatus::Closed, $result->status);
    }

    public function test_close_service_rejects_draft_competitions(): void
    {
        $organizer = User::factory()->organizer()->create();
        $competition = Competition::factory()->create(['organization_id' => $organizer->organization_id]);

        $this->expectException(InvalidCompetitionStatusTransitionException::class);

        (new CloseCompetitionService)->execute($organizer, $competition);
    }
}
