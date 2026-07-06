<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Models\ParticipantProfile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class ShowParticipantProfileService
{
    public function execute(User $actor, User $subject): ParticipantProfile
    {
        $profile = $subject->participantProfile;

        if ($profile === null) {
            throw new AuthorizationException('Participant profile not found.');
        }

        if (! $actor->can('view', $profile)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        return $profile;
    }
}
