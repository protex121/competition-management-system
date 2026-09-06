<?php

declare(strict_types=1);

namespace App\Policies\Judging;

use App\Models\Competition;
use App\Models\User;

class CompetitionJudgePolicy
{
    public function manage(User $actor, Competition $competition): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $actor->isOrganizer()
            && $actor->organization_id !== null
            && $actor->organization_id === $competition->organization_id;
    }
}
