<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Team;

use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Enums\TeamStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Organization;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\Team\ApproveTeamService;
use App\Services\Team\RejectTeamService;
use App\Services\Team\SubmitTeamForApprovalService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeamApprovalServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_team_for_approval_service_transitions_status(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
            'min_team_size' => 2,
        ]);
        $team = Team::factory()->create(['competition_id' => $competition->id]);
        $captain = User::query()->findOrFail($team->captain_user_id);
        $member = User::factory()->create(['organization_id' => $organization->id]);
        TeamMember::factory()->create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => TeamMemberRole::Member,
            'status' => TeamMemberStatus::Active,
        ]);

        $updated = (new SubmitTeamForApprovalService)->execute($captain, $team);

        $this->assertSame(TeamStatus::PendingApproval, $updated->status);
        $this->assertNotNull($updated->submitted_at);
    }

    public function test_submit_team_for_approval_service_blocks_under_min_roster(): void
    {
        $competition = Competition::factory()->teamMode()->published()->create([
            'min_team_size' => 3,
        ]);
        $team = Team::factory()->create(['competition_id' => $competition->id]);
        $captain = User::query()->findOrFail($team->captain_user_id);

        $this->expectException(ValidationException::class);

        (new SubmitTeamForApprovalService)->execute($captain, $team);
    }

    public function test_approve_team_service_approves_pending_team(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
        ]);
        $team = Team::factory()->pendingApproval()->create(['competition_id' => $competition->id]);
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);

        $approved = (new ApproveTeamService)->execute($organizer, $team);

        $this->assertSame(TeamStatus::Approved, $approved->status);
        $this->assertNotNull($approved->approved_at);
    }

    public function test_reject_team_service_rejects_pending_team(): void
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->teamMode()->published()->create([
            'organization_id' => $organization->id,
        ]);
        $team = Team::factory()->pendingApproval()->create(['competition_id' => $competition->id]);
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);

        $rejected = (new RejectTeamService)->execute($organizer, $team, [
            'rejection_reason' => 'Incomplete roster details',
        ]);

        $this->assertSame(TeamStatus::Rejected, $rejected->status);
        $this->assertSame('Incomplete roster details', $rejected->rejection_reason);
    }

    public function test_approve_team_service_denies_participant(): void
    {
        $team = Team::factory()->pendingApproval()->create();
        $participant = User::factory()->create(['role' => UserRole::Participant]);

        $this->expectException(AuthorizationException::class);

        (new ApproveTeamService)->execute($participant, $team);
    }
}
