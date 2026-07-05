<?php

declare(strict_types=1);

namespace App\Policies\Identity;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isSuperAdmin() || $actor->isOrganizer();
    }

    public function view(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return true;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $actor->isOrganizer() && $actor->sharesOrganizationWith($target);
    }

    public function create(User $actor): bool
    {
        return $actor->isSuperAdmin() || $actor->isOrganizer();
    }

    public function update(User $actor, User $target): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($target->isSuperAdmin()) {
            return false;
        }

        return $actor->isOrganizer()
            && $actor->id !== $target->id
            && $actor->sharesOrganizationWith($target);
    }

    public function delete(User $actor, User $target): bool
    {
        return $this->canRemoveUser($actor, $target);
    }

    public function deactivate(User $actor, User $target): bool
    {
        if ($target->isDeactivated()) {
            return false;
        }

        return $this->canRemoveUser($actor, $target);
    }

    public function reactivate(User $actor, User $target): bool
    {
        if (! $target->isDeactivated()) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $actor->isOrganizer() && $actor->sharesOrganizationWith($target);
    }

    public function restore(User $actor, User $target): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $actor->isOrganizer() && $actor->sharesOrganizationWith($target);
    }

    private function canRemoveUser(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return false;
        }

        if ($target->isSuperAdmin()) {
            return false;
        }

        if ($target->isOrganizer() && $this->isLastOrganizer($target)) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        if (! $actor->isOrganizer() || ! $actor->sharesOrganizationWith($target)) {
            return false;
        }

        return true;
    }

    private function isLastOrganizer(User $target): bool
    {
        return User::query()
            ->where('organization_id', $target->organization_id)
            ->where('role', UserRole::Organizer)
            ->whereNull('deactivated_at')
            ->count() <= 1;
    }
}
