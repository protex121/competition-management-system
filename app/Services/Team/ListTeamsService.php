<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Enums\TeamMemberStatus;
use App\Models\Competition;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListTeamsService
{
    public function execute(User $actor, Competition $competition): LengthAwarePaginator
    {
        if (! $actor->can('viewAny', Team::class)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $query = Team::withoutGlobalScopes()
            ->with(['captain', 'members' => fn ($q) => $q->where('status', TeamMemberStatus::Active)])
            ->where('competition_id', $competition->id)
            ->latest();

        if (! $actor->isSuperAdmin() && ! $actor->isOrganizer()) {
            $query->whereHas('members', function ($memberQuery) use ($actor): void {
                $memberQuery
                    ->where('user_id', $actor->id)
                    ->where('status', TeamMemberStatus::Active);
            });
        }

        return $query->paginate(15);
    }
}
