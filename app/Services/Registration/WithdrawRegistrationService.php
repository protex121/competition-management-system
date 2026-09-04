<?php

declare(strict_types=1);

namespace App\Services\Registration;

use App\Enums\RegistrationStatus;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class WithdrawRegistrationService
{
    public function execute(User $actor, Registration $registration): Registration
    {
        if (! $actor->can('withdraw', $registration)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $registration->update([
            'status' => RegistrationStatus::Withdrawn,
            'withdrawn_at' => now(),
        ]);

        return $registration->refresh();
    }
}
