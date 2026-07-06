<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Enums\InvitationStatus;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPendingInvitationsService
{
    public function execute(User $actor): LengthAwarePaginator
    {
        return TeamInvitation::query()
            ->with(['team.competition', 'invitedBy'])
            ->where('invited_user_id', $actor->id)
            ->where('status', InvitationStatus::Pending)
            ->where('expires_at', '>', now())
            ->latest()
            ->paginate(15);
    }
}
