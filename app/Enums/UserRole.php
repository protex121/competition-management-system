<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super-admin';
    case Organizer = 'organizer';
    case Committee = 'committee';
    case Judge = 'judge';
    case Participant = 'participant';
    case Coach = 'coach';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Organizer => 'Organizer',
            self::Committee => 'Committee',
            self::Judge => 'Judge',
            self::Participant => 'Participant',
            self::Coach => 'Coach',
        };
    }

    /**
     * @return list<self>
     */
    public static function assignable(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $role): bool => $role !== self::SuperAdmin,
        ));
    }
}
