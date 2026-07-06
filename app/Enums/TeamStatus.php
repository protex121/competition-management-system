<?php

declare(strict_types=1);

namespace App\Enums;

enum TeamStatus: string
{
    case Forming = 'forming';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Forming => 'Forming',
            self::PendingApproval => 'Pending Approval',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }
}
