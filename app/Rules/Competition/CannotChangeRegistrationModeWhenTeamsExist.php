<?php

declare(strict_types=1);

namespace App\Rules\Competition;

use App\Models\Competition;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CannotChangeRegistrationModeWhenTeamsExist implements ValidationRule
{
    public function __construct(
        private readonly Competition $competition,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->competition->teams()->withoutGlobalScopes()->exists()) {
            return;
        }

        if ($value === $this->competition->registration_mode->value) {
            return;
        }

        $fail('Registration mode cannot be changed after teams have been created.');
    }
}
