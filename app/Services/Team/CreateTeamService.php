<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Enums\TeamStatus;
use App\Models\Competition;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateTeamService
{
    /**
     * @param  array{name: string}  $data
     */
    public function execute(User $actor, Competition $competition, array $data): Team
    {
        if (! $actor->can('create', [Team::class, $competition])) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        if ($competition->isClosed()) {
            throw ValidationException::withMessages([
                'competition' => ['Teams cannot be created for a closed competition.'],
            ]);
        }

        if ($this->userHasActiveTeamInCompetition($actor, $competition)) {
            throw ValidationException::withMessages([
                'team' => ['You are already on a team in this competition.'],
            ]);
        }

        return DB::transaction(function () use ($actor, $competition, $data): Team {
            $team = Team::withoutGlobalScopes()->create([
                'competition_id' => $competition->id,
                'name' => $data['name'],
                'captain_user_id' => $actor->id,
                'status' => TeamStatus::Forming,
            ]);

            TeamMember::query()->create([
                'team_id' => $team->id,
                'user_id' => $actor->id,
                'role' => TeamMemberRole::Captain,
                'status' => TeamMemberStatus::Active,
                'joined_at' => now(),
            ]);

            return $team->load(['competition', 'captain', 'members.user']);
        });
    }

    private function userHasActiveTeamInCompetition(User $user, Competition $competition): bool
    {
        return TeamMember::query()
            ->join('teams', 'teams.id', '=', 'team_members.team_id')
            ->where('team_members.user_id', $user->id)
            ->where('team_members.status', TeamMemberStatus::Active)
            ->where('teams.competition_id', $competition->id)
            ->whereNull('teams.deleted_at')
            ->exists();
    }
}
