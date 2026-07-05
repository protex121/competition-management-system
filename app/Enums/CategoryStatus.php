<?php

declare(strict_types=1);

namespace App\Enums;

enum CategoryStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Disabled = 'disabled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Disabled => 'Disabled',
            self::Archived => 'Archived',
        };
    }
}
