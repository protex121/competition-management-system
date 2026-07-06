<?php

declare(strict_types=1);

namespace App\Policies\Team;

use App\Enums\UserRole;
use App\Models\ParticipantProfile;
use App\Models\User;

class ParticipantProfilePolicy
{
    public function view(User $actor, ParticipantProfile $profile): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($actor->id === $profile->user_id) {
            return true;
        }

        return $actor->isOrganizer()
            && $actor->organization_id !== null
            && $actor->organization_id === $profile->user()->value('organization_id');
    }

    public function create(User $actor): bool
    {
        return $actor->isSuperAdmin() || $actor->role === UserRole::Participant;
    }

    public function update(User $actor, ParticipantProfile $profile): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $actor->id === $profile->user_id;
    }

    public function delete(User $actor, ParticipantProfile $profile): bool
    {
        return $actor->isSuperAdmin();
    }
}
