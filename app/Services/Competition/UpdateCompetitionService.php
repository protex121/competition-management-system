<?php

declare(strict_types=1);

namespace App\Services\Competition;

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

        $competition->fill($attributes);
        $competition->save();

        return $competition;
    }
}
