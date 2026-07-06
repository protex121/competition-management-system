<?php

declare(strict_types=1);

namespace App\Services\Competition;

use App\Enums\CompetitionStatus;
use App\Enums\TeamMemberStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

class ListParticipantCompetitionsService
{
    public function execute(User $actor): LengthAwarePaginator
    {
        if ($actor->role !== UserRole::Participant) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        if ($actor->organization_id === null) {
            return new Paginator([], 0, 15);
        }

        $paginator = Competition::query()
            ->where('organization_id', $actor->organization_id)
            ->whereIn('status', [CompetitionStatus::Published, CompetitionStatus::Active])
            ->orderByDesc('starts_at')
            ->paginate(15);

        $paginator->getCollection()->transform(function (Competition $competition) use ($actor): array {
            $myTeam = null;

            if ($competition->allowsTeams()) {
                $team = Team::withoutGlobalScopes()
                    ->where('competition_id', $competition->id)
                    ->whereHas('members', function ($query) use ($actor): void {
                        $query
                            ->where('user_id', $actor->id)
                            ->where('status', TeamMemberStatus::Active);
                    })
                    ->first(['id', 'name', 'status']);

                if ($team !== null) {
                    $myTeam = [
                        'id' => $team->id,
                        'name' => $team->name,
                        'status' => $team->status->value,
                    ];
                }
            }

            return [
                'id' => $competition->id,
                'name' => $competition->name,
                'slug' => $competition->slug,
                'status' => $competition->status->value,
                'registration_mode' => $competition->registration_mode->value,
                'allows_teams' => $competition->allowsTeams(),
                'starts_at' => $competition->starts_at?->toISOString(),
                'registration_ends_at' => $competition->registration_ends_at?->toISOString(),
                'my_team' => $myTeam,
            ];
        });

        return $paginator;
    }
}
