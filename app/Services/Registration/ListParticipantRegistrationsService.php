<?php

declare(strict_types=1);

namespace App\Services\Registration;

use App\Enums\TeamMemberStatus;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ListParticipantRegistrationsService
{
    /**
     * @return Collection<int, Registration>
     */
    public function execute(User $actor): Collection
    {
        return Registration::withoutGlobalScopes()
            ->where(function ($query) use ($actor): void {
                $query->where('user_id', $actor->id)
                    ->orWhereIn('team_id', function ($subQuery) use ($actor): void {
                        $subQuery->select('team_id')
                            ->from('team_members')
                            ->where('user_id', $actor->id)
                            ->where('status', TeamMemberStatus::Active);
                    });
            })
            ->with(['category.competition', 'user', 'team'])
            ->orderByDesc('created_at')
            ->get();
    }
}
