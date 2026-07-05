<?php

declare(strict_types=1);

namespace App\Policies\Competition;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\User;

class CompetitionPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isSuperAdmin() || $actor->isOrganizer();
    }

    public function view(User $actor, Competition $competition): bool
    {
        if ($this->canManageCompetition($actor, $competition)) {
            return true;
        }

        return in_array($competition->status, [
            CompetitionStatus::Published,
            CompetitionStatus::Active,
            CompetitionStatus::Closed,
        ], true);
    }

    public function create(User $actor): bool
    {
        return $actor->isSuperAdmin() || $actor->isOrganizer();
    }

    public function update(User $actor, Competition $competition): bool
    {
        if ($competition->isClosed()) {
            return false;
        }

        return $this->canManageCompetition($actor, $competition);
    }

    public function delete(User $actor, Competition $competition): bool
    {
        if (! $competition->isDraft()) {
            return false;
        }

        return $this->canManageCompetition($actor, $competition);
    }

    public function publish(User $actor, Competition $competition): bool
    {
        if (! $competition->isDraft()) {
            return false;
        }

        return $this->canManageCompetition($actor, $competition);
    }

    public function activate(User $actor, Competition $competition): bool
    {
        if (! $competition->isPublished()) {
            return false;
        }

        return $this->canManageCompetition($actor, $competition);
    }

    public function close(User $actor, Competition $competition): bool
    {
        if (! $competition->isPublished() && ! $competition->isActive()) {
            return false;
        }

        return $this->canManageCompetition($actor, $competition);
    }

    private function canManageCompetition(User $actor, Competition $competition): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $actor->isOrganizer()
            && $actor->organization_id !== null
            && $actor->organization_id === $competition->organization_id;
    }
}
