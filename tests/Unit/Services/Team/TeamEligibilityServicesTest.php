<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Team;

use App\Enums\TeamStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use App\Services\Team\CheckParticipantEligibilityService;
use App\Services\Team\CheckTeamEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamEligibilityServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_eligibility_passes_for_valid_user(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
        ]);
        $participant = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);

        $result = (new CheckParticipantEligibilityService)->execute($participant, $competition);

        $this->assertTrue($result->eligible);
        $this->assertSame([], $result->reasons);
    }

    public function test_participant_eligibility_fails_when_already_on_team(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
        ]);
        $team = Team::factory()->create(['competition_id' => $competition->id]);
        $participant = User::query()->findOrFail($team->captain_user_id);

        $result = (new CheckParticipantEligibilityService)->execute($participant, $competition);

        $this->assertFalse($result->eligible);
        $this->assertNotEmpty($result->reasons);
    }

    public function test_team_eligibility_passes_for_approved_team_with_coach(): void
    {
        $competition = Competition::factory()->teamMode()->published()->create([
            'min_team_size' => 1,
            'max_team_size' => 5,
            'requires_coach' => true,
        ]);
        $coach = User::factory()->create(['role' => UserRole::Coach]);
        $team = Team::factory()->approved()->create([
            'competition_id' => $competition->id,
            'coach_user_id' => $coach->id,
        ]);

        $result = (new CheckTeamEligibilityService)->execute($team, $competition);

        $this->assertTrue($result->eligible);
    }

    public function test_team_eligibility_fails_without_required_coach(): void
    {
        $competition = Competition::factory()->teamMode()->published()->create([
            'requires_coach' => true,
        ]);
        $team = Team::factory()->approved()->create([
            'competition_id' => $competition->id,
            'coach_user_id' => null,
        ]);

        $result = (new CheckTeamEligibilityService)->execute($team, $competition);

        $this->assertFalse($result->eligible);
        $this->assertContains('Team must have an assigned coach.', $result->reasons);
    }

    public function test_team_eligibility_fails_when_not_approved(): void
    {
        $competition = Competition::factory()->teamMode()->published()->create();
        $team = Team::factory()->create([
            'competition_id' => $competition->id,
            'status' => TeamStatus::Forming,
        ]);

        $result = (new CheckTeamEligibilityService)->execute($team, $competition);

        $this->assertFalse($result->eligible);
    }
}
