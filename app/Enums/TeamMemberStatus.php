<?php

declare(strict_types=1);

namespace App\Enums;

enum TeamMemberStatus: string
{
    case Active = 'active';
    case Removed = 'removed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Removed => 'Removed',
        };
    }
}
