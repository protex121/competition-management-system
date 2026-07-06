<?php

declare(strict_types=1);

namespace App\Enums;

enum RegistrationMode: string
{
    case Individual = 'individual';
    case Team = 'team';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Individual',
            self::Team => 'Team',
            self::Both => 'Individual & Team',
        };
    }

    public function allowsTeams(): bool
    {
        return $this === self::Team || $this === self::Both;
    }

    public function allowsIndividual(): bool
    {
        return $this === self::Individual || $this === self::Both;
    }
}
