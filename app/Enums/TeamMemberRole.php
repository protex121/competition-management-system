<?php

declare(strict_types=1);

namespace App\Enums;

enum TeamMemberRole: string
{
    case Captain = 'captain';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Captain => 'Captain',
            self::Member => 'Member',
        };
    }
}
