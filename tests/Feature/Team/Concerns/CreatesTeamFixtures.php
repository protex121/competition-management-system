<?php

declare(strict_types=1);

namespace Tests\Feature\Team\Concerns;

use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Organization;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;

trait CreatesTeamFixtures
{
    /**
     * @param  array<string, mixed>  $competitionAttributes
     * @return array{0: Organization, 1: Competition}
     */
    protected function createTeamCompetitionContext(array $competitionAttributes = []): array
    {
        $organization = Organization::factory()->create();
        $competition = Competition::factory()->teamMode()->published()->create(array_merge([
            'organization_id' => $organization->id,
        ], $competitionAttributes));

        return [$organization, $competition];
    }

    protected function createParticipantFor(Organization $organization): User
    {
        return User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);
    }

    protected function createOrganizerFor(Organization $organization): User
    {
        return User::factory()->organizer()->create([
            'organization_id' => $organization->id,
        ]);
    }

    protected function createTeamForCaptain(Competition $competition, User $captain): Team
    {
        return Team::factory()->create([
            'competition_id' => $competition->id,
            'captain_user_id' => $captain->id,
        ]);
    }

    protected function addTeamMember(Team $team, User $user, TeamMemberRole $role = TeamMemberRole::Member): TeamMember
    {
        return TeamMember::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => TeamMemberStatus::Active,
        ]);
    }
}
