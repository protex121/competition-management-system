<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Models\ParticipantProfile;
use App\Models\User;

class UpsertParticipantProfileService
{
    /**
     * @param  array{
     *     bio?: string|null,
     *     phone?: string|null,
     *     institution?: string|null,
     * }  $data
     */
    public function execute(User $actor, array $data): ParticipantProfile
    {
        return ParticipantProfile::query()->updateOrCreate(
            ['user_id' => $actor->id],
            [
                'bio' => $data['bio'] ?? null,
                'phone' => $data['phone'] ?? null,
                'institution' => $data['institution'] ?? null,
            ],
        );
    }
}
