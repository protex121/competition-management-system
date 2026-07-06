<?php

declare(strict_types=1);

namespace App\Services\Competition;

use App\Enums\RegistrationMode;
use App\Models\Competition;

class UpdateCompetitionService
{
    /**
     * @param  array{
     *     name: string,
     *     slug?: string|null,
     *     description?: string|null,
     *     starts_at?: string|null,
     *     ends_at?: string|null,
     *     registration_starts_at?: string|null,
     *     registration_ends_at?: string|null,
     *     max_participants?: int|null,
     *     registration_mode?: string,
     *     min_team_size?: int|null,
     *     max_team_size?: int|null,
     *     requires_coach?: bool,
     * }  $data
     */
    public function execute(Competition $competition, array $data): Competition
    {
        $attributes = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'registration_starts_at' => $data['registration_starts_at'] ?? null,
            'registration_ends_at' => $data['registration_ends_at'] ?? null,
            'max_participants' => $data['max_participants'] ?? null,
        ];

        if (array_key_exists('slug', $data)) {
            $attributes['slug'] = $data['slug'];
        }

        if ($competition->isDraft() && array_key_exists('registration_mode', $data)) {
            $attributes = array_merge($attributes, $this->registrationAttributes($data));
        }

        $competition->fill($attributes);
        $competition->save();

        return $competition;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     registration_mode: RegistrationMode,
     *     min_team_size: int|null,
     *     max_team_size: int|null,
     *     requires_coach: bool,
     * }
     */
    private function registrationAttributes(array $data): array
    {
        $mode = RegistrationMode::from($data['registration_mode']);

        if (! $mode->allowsTeams()) {
            return [
                'registration_mode' => $mode,
                'min_team_size' => null,
                'max_team_size' => null,
                'requires_coach' => false,
            ];
        }

        return [
            'registration_mode' => $mode,
            'min_team_size' => $data['min_team_size'] ?? null,
            'max_team_size' => $data['max_team_size'] ?? null,
            'requires_coach' => (bool) ($data['requires_coach'] ?? false),
        ];
    }
}
