<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Competition;

use App\Enums\CategoryStatus;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Organization;
use App\Models\User;
use App\Services\Competition\ActivateCategoryService;
use App\Services\Competition\CreateCategoryService;
use App\Services\Competition\DeleteCategoryService;
use App\Services\Competition\DisableCategoryService;
use App\Services\Competition\UpdateCategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCrudServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_category_service_creates_draft_category_with_unique_slug(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);
        CompetitionCategory::factory()->create([
            'competition_id' => $competition->id,
            'slug' => 'junior',
        ]);

        $service = new CreateCategoryService;
        $category = $service->execute($organizer, $competition, [
            'name' => 'Junior',
        ]);

        $this->assertSame('Junior', $category->name);
        $this->assertSame('junior-2', $category->slug);
        $this->assertSame(CategoryStatus::Draft, $category->status);
        $this->assertFalse($category->isDefault());
        $this->assertSame($competition->id, $category->competition_id);
    }

    public function test_create_category_service_increments_sort_order(): void
    {
        $competition = Competition::factory()->create();
        CompetitionCategory::factory()->create([
            'competition_id' => $competition->id,
            'sort_order' => 3,
        ]);
        $organizer = User::factory()->organizer()->create(['organization_id' => $competition->organization_id]);

        $service = new CreateCategoryService;
        $category = $service->execute($organizer, $competition, [
            'name' => 'Senior',
        ]);

        $this->assertSame(4, $category->sort_order);
    }

    public function test_update_category_service_updates_fields(): void
    {
        $category = CompetitionCategory::factory()->create([
            'name' => 'Open',
            'slug' => 'open',
        ]);

        $service = new UpdateCategoryService;
        $updated = $service->execute($category, [
            'name' => 'Open Track',
            'slug' => 'open-track',
            'description' => 'For all ages',
            'max_participants' => 100,
        ]);

        $this->assertSame('Open Track', $updated->name);
        $this->assertSame('open-track', $updated->slug);
        $this->assertSame('For all ages', $updated->description);
        $this->assertSame(100, $updated->max_participants);
    }

    public function test_delete_category_service_soft_deletes_category(): void
    {
        $category = CompetitionCategory::factory()->create();

        (new DeleteCategoryService)->execute($category);

        $this->assertSoftDeleted('competition_categories', ['id' => $category->id]);
    }

    public function test_activate_category_service_sets_status_to_active(): void
    {
        $organizer = User::factory()->organizer()->create();
        $category = CompetitionCategory::factory()->create(['status' => CategoryStatus::Draft]);

        $updated = (new ActivateCategoryService)->execute($organizer, $category);

        $this->assertSame(CategoryStatus::Active, $updated->status);
    }

    public function test_disable_category_service_sets_status_to_disabled(): void
    {
        $organizer = User::factory()->organizer()->create();
        $category = CompetitionCategory::factory()->active()->create();

        $updated = (new DisableCategoryService)->execute($organizer, $category);

        $this->assertSame(CategoryStatus::Disabled, $updated->status);
    }
}
