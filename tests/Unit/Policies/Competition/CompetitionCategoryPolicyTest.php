<?php

declare(strict_types=1);

namespace Tests\Unit\Policies\Competition;

use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Organization;
use App\Models\User;
use App\Policies\Competition\CompetitionCategoryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionCategoryPolicyTest extends TestCase
{
    use RefreshDatabase;

    private CompetitionCategoryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new CompetitionCategoryPolicy;
    }

    public function test_organizer_can_create_categories_for_competitions_in_their_organization(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);

        $this->assertTrue($this->policy->create($organizer, $competition));
    }

    public function test_organizer_cannot_create_categories_for_closed_competitions(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->closed()->create(['organization_id' => $organization->id]);

        $this->assertFalse($this->policy->create($organizer, $competition));
    }

    public function test_organizer_cannot_create_categories_for_competitions_in_another_organization(): void
    {
        $organizer = User::factory()->organizer()->create();
        $competition = Competition::factory()->create();

        $this->assertFalse($this->policy->create($organizer, $competition));
    }

    public function test_organizer_can_update_categories_when_competition_is_not_closed(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $category = CompetitionCategory::factory()->create(['competition_id' => $competition->id]);

        $this->assertTrue($this->policy->update($organizer, $category));
    }

    public function test_organizer_cannot_update_categories_when_competition_is_closed(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->closed()->create(['organization_id' => $organization->id]);
        $category = CompetitionCategory::factory()->create(['competition_id' => $competition->id]);

        $this->assertFalse($this->policy->update($organizer, $category));
    }

    public function test_default_category_cannot_be_deleted(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);
        $category = CompetitionCategory::factory()->defaultGeneral()->create(['competition_id' => $competition->id]);

        $this->assertFalse($this->policy->delete($organizer, $category));
    }

    public function test_organizer_can_delete_non_default_categories(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);
        $category = CompetitionCategory::factory()->create(['competition_id' => $competition->id]);

        $this->assertTrue($this->policy->delete($organizer, $category));
    }

    public function test_category_activate_and_disable_require_published_or_active_parent(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);

        $draftCompetition = Competition::factory()->create(['organization_id' => $organization->id]);
        $draftCategory = CompetitionCategory::factory()->create(['competition_id' => $draftCompetition->id]);

        $this->assertFalse($this->policy->activate($organizer, $draftCategory));
        $this->assertFalse($this->policy->disable($organizer, $draftCategory));

        $publishedCompetition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $publishedCategory = CompetitionCategory::factory()->create(['competition_id' => $publishedCompetition->id]);

        $this->assertTrue($this->policy->activate($organizer, $publishedCategory));
        $this->assertTrue($this->policy->disable($organizer, $publishedCategory));

        $closedCompetition = Competition::factory()->closed()->create(['organization_id' => $organization->id]);
        $closedCategory = CompetitionCategory::factory()->create(['competition_id' => $closedCompetition->id]);

        $this->assertFalse($this->policy->activate($organizer, $closedCategory));
        $this->assertFalse($this->policy->disable($organizer, $closedCategory));
    }

    public function test_super_admin_can_manage_categories_across_organizations(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $competition = Competition::factory()->published()->create();
        $category = CompetitionCategory::factory()->create(['competition_id' => $competition->id]);

        $this->assertTrue($this->policy->create($superAdmin, $competition));
        $this->assertTrue($this->policy->update($superAdmin, $category));
        $this->assertTrue($this->policy->activate($superAdmin, $category));
    }
}
