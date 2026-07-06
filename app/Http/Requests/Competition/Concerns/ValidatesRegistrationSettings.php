<?php

declare(strict_types=1);

namespace App\Http\Requests\Competition\Concerns;

use App\Enums\RegistrationMode;
use App\Models\Competition;
use App\Rules\Competition\CannotChangeRegistrationModeWhenTeamsExist;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

trait ValidatesRegistrationSettings
{
    /**
     * @return array<string, mixed>
     */
    protected function registrationSettingsRules(?Competition $competition = null): array
    {
        $editable = $competition === null || $competition->isDraft();

        if (! $editable) {
            return [
                'registration_mode' => ['prohibited'],
                'min_team_size' => ['prohibited'],
                'max_team_size' => ['prohibited'],
                'requires_coach' => ['prohibited'],
            ];
        }

        $modeRule = ['required', new Enum(RegistrationMode::class)];

        if ($competition !== null) {
            $modeRule[] = new CannotChangeRegistrationModeWhenTeamsExist($competition);
        }

        return [
            'registration_mode' => $modeRule,
            'min_team_size' => [
                Rule::requiredIf(fn (): bool => $this->requiresTeamSizes()),
                'nullable',
                'integer',
                'min:1',
            ],
            'max_team_size' => [
                Rule::requiredIf(fn (): bool => $this->requiresTeamSizes()),
                'nullable',
                'integer',
                'min:1',
                'gte:min_team_size',
            ],
            'requires_coach' => ['sometimes', 'boolean'],
        ];
    }

    protected function requiresTeamSizes(): bool
    {
        $mode = $this->input('registration_mode');

        return in_array($mode, [
            RegistrationMode::Team->value,
            RegistrationMode::Both->value,
        ], true);
    }
}
