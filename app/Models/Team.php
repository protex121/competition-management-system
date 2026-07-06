<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamMemberStatus;
use App\Enums\TeamStatus;
use App\Models\Scopes\CompetitionOrganizationScope;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'competition_id',
        'name',
        'captain_user_id',
        'coach_user_id',
        'status',
        'rejection_reason',
        'submitted_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TeamStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new CompetitionOrganizationScope);
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function captain(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captain_user_id');
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    public function isForming(): bool
    {
        return $this->status === TeamStatus::Forming;
    }

    public function isPendingApproval(): bool
    {
        return $this->status === TeamStatus::PendingApproval;
    }

    public function isApproved(): bool
    {
        return $this->status === TeamStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === TeamStatus::Rejected;
    }

    public function activeMemberCount(): int
    {
        return $this->members()
            ->where('status', TeamMemberStatus::Active)
            ->count();
    }
}
