<?php

declare(strict_types=1);

namespace Tests\Unit\Policies\Judging;

use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\CompetitionJudge;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Submission;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Policies\Judging\ScorePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScorePolicyTest extends TestCase
{
    use RefreshDatabase;

    private ScorePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ScorePolicy;
    }

    private function makeJudge(Organization $organization): User
    {
        return User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Judge,
        ]);
    }

    public function test_assigned_judge_can_score_finalized_submission_not_their_own(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $category = CompetitionCategory::factory()->active()->create(['competition_id' => $competition->id]);
        $registration = Registration::factory()->create(['competition_category_id' => $category->id]);
        $submission = Submission::factory()->finalized()->create(['registration_id' => $registration->id]);
        $judge = $this->makeJudge($organization);
        CompetitionJudge::factory()->create(['competition_id' => $competition->id, 'user_id' => $judge->id]);

        $this->assertTrue($this->policy->manage($judge, $submission));
    }

    public function test_judge_cannot_score_their_own_individual_submission(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $category = CompetitionCategory::factory()->active()->create(['competition_id' => $competition->id]);
        $judge = $this->makeJudge($organization);
        $registration = Registration::factory()->create([
            'competition_category_id' => $category->id,
            'user_id' => $judge->id,
        ]);
        $submission = Submission::factory()->finalized()->create(['registration_id' => $registration->id]);
        CompetitionJudge::factory()->create(['competition_id' => $competition->id, 'user_id' => $judge->id]);

        $this->assertFalse($this->policy->manage($judge, $submission));
    }

    public function test_judge_cannot_score_submission_of_team_they_are_active_member_of(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->teamMode()->published()->create(['organization_id' => $organization->id]);
        $category = CompetitionCategory::factory()->active()->create(['competition_id' => $competition->id]);
        $team = Team::factory()->approved()->create(['competition_id' => $competition->id]);
        $judge = $this->makeJudge($organization);
        TeamMember::factory()->create([
            'team_id' => $team->id,
            'user_id' => $judge->id,
            'role' => TeamMemberRole::Member,
            'status' => TeamMemberStatus::Active,
        ]);
        $registration = Registration::factory()->create([
            'competition_category_id' => $category->id,
            'user_id' => null,
            'team_id' => $team->id,
        ]);
        $submission = Submission::factory()->finalized()->create(['registration_id' => $registration->id]);
        CompetitionJudge::factory()->create(['competition_id' => $competition->id, 'user_id' => $judge->id]);

        $this->assertFalse($this->policy->manage($judge, $submission));
    }

    public function test_unassigned_judge_cannot_score(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $category = CompetitionCategory::factory()->active()->create(['competition_id' => $competition->id]);
        $registration = Registration::factory()->create(['competition_category_id' => $category->id]);
        $submission = Submission::factory()->finalized()->create(['registration_id' => $registration->id]);
        $judge = $this->makeJudge($organization);

        $this->assertFalse($this->policy->manage($judge, $submission));
    }

    public function test_cannot_score_non_finalized_submission(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $category = CompetitionCategory::factory()->active()->create(['competition_id' => $competition->id]);
        $registration = Registration::factory()->create(['competition_category_id' => $category->id]);
        $submission = Submission::factory()->create(['registration_id' => $registration->id]);
        $judge = $this->makeJudge($organization);
        CompetitionJudge::factory()->create(['competition_id' => $competition->id, 'user_id' => $judge->id]);

        $this->assertFalse($this->policy->manage($judge, $submission));
    }

    public function test_cannot_score_when_competition_closed(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->closed()->create(['organization_id' => $organization->id]);
        $category = CompetitionCategory::factory()->active()->create(['competition_id' => $competition->id]);
        $registration = Registration::factory()->create(['competition_category_id' => $category->id]);
        $submission = Submission::factory()->finalized()->create(['registration_id' => $registration->id]);
        $judge = $this->makeJudge($organization);
        CompetitionJudge::factory()->create(['competition_id' => $competition->id, 'user_id' => $judge->id]);

        $this->assertFalse($this->policy->manage($judge, $submission));
    }

    public function test_view_any_true_for_organizer_false_for_participant(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->published()->create(['organization_id' => $organization->id]);
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);

        $this->assertTrue($this->policy->viewAny($organizer, $competition));
        $this->assertFalse($this->policy->viewAny($participant, $competition));
    }
}
