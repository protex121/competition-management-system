<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use Database\Factories\TeamMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    /** @use HasFactory<TeamMemberFactory> */
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'role',
        'status',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => TeamMemberRole::class,
            'status' => TeamMemberStatus::class,
            'joined_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === TeamMemberStatus::Active;
    }

    public function isCaptain(): bool
    {
        return $this->role === TeamMemberRole::Captain;
    }
}
