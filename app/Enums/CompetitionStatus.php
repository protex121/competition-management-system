<?php

declare(strict_types=1);

namespace App\Enums;

enum CompetitionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Active = 'active';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Active => 'Active',
            self::Closed => 'Closed',
        };
    }
}
