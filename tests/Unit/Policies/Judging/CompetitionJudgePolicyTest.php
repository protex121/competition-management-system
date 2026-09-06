<?php

declare(strict_types=1);

namespace Tests\Unit\Policies\Judging;

use App\Models\Competition;
use App\Models\Organization;
use App\Models\User;
use App\Policies\Judging\CompetitionJudgePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionJudgePolicyTest extends TestCase
{
    use RefreshDatabase;

    private CompetitionJudgePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new CompetitionJudgePolicy;
    }

    public function test_organizer_can_manage_judges_in_their_org(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);

        $this->assertTrue($this->policy->manage($organizer, $competition));
    }

    public function test_organizer_from_other_org_cannot_manage_judges(): void
    {
        $competition = Competition::factory()->create();
        $organizer = User::factory()->organizer()->create();

        $this->assertFalse($this->policy->manage($organizer, $competition));
    }

    public function test_super_admin_can_manage_judges(): void
    {
        $competition = Competition::factory()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $this->assertTrue($this->policy->manage($superAdmin, $competition));
    }
}
