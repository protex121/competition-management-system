<?php

declare(strict_types=1);

namespace App\Enums;

enum RegistrationStatus: string
{
    case Confirmed = 'confirmed';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmed',
            self::Withdrawn => 'Withdrawn',
        };
    }
}
