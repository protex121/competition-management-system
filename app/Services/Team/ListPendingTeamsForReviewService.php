<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Enums\TeamMemberStatus;
use App\Enums\TeamStatus;
use App\Models\Competition;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPendingTeamsForReviewService
{
    public function execute(User $actor, Competition $competition): LengthAwarePaginator
    {
        if (! $actor->can('view', $competition)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        if (! $actor->isSuperAdmin() && ! $actor->isOrganizer()) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        return Team::withoutGlobalScopes()
            ->with([
                'captain',
                'members' => fn ($query) => $query
                    ->where('status', TeamMemberStatus::Active)
                    ->with('user'),
            ])
            ->where('competition_id', $competition->id)
            ->where('status', TeamStatus::PendingApproval)
            ->orderByDesc('submitted_at')
            ->paginate(15);
    }
}
