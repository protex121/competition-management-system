<?php

declare(strict_types=1);

namespace Tests\Unit\Policies\Judging;

use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\CompetitionJudge;
use App\Models\Organization;
use App\Models\User;
use App\Policies\Judging\RubricPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RubricPolicyTest extends TestCase
{
    use RefreshDatabase;

    private RubricPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new RubricPolicy;
    }

    public function test_organizer_can_manage_criteria(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);

        $this->assertTrue($this->policy->manageCriteria($organizer, $competition));
        $this->assertTrue($this->policy->view($organizer, $competition));
    }

    public function test_assigned_active_judge_can_view_rubric(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);
        $judge = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Judge,
        ]);
        CompetitionJudge::factory()->create([
            'competition_id' => $competition->id,
            'user_id' => $judge->id,
        ]);

        $this->assertTrue($this->policy->view($judge, $competition));
        $this->assertFalse($this->policy->manageCriteria($judge, $competition));
    }

    public function test_unassigned_judge_cannot_view_rubric(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);
        $judge = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Judge,
        ]);

        $this->assertFalse($this->policy->view($judge, $competition));
    }

    public function test_participant_cannot_view_rubric(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->create(['organization_id' => $organization->id]);
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);

        $this->assertFalse($this->policy->view($participant, $competition));
    }
}
