<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RegistrationStatus;
use App\Models\Scopes\RegistrationOrganizationScope;
use Database\Factories\RegistrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    /** @use HasFactory<RegistrationFactory> */
    use HasFactory;

    protected $fillable = [
        'competition_category_id',
        'user_id',
        'team_id',
        'status',
        'withdrawn_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'withdrawn_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new RegistrationOrganizationScope);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function isConfirmed(): bool
    {
        return $this->status === RegistrationStatus::Confirmed;
    }

    public function isWithdrawn(): bool
    {
        return $this->status === RegistrationStatus::Withdrawn;
    }

    public function isIndividual(): bool
    {
        return $this->user_id !== null;
    }

    public function isTeam(): bool
    {
        return $this->team_id !== null;
    }
}
