<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CompetitionStatus;
use App\Enums\RegistrationMode;
use App\Models\Scopes\OrganizationScope;
use Database\Factories\CompetitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Competition extends Model
{
    /** @use HasFactory<CompetitionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'description',
        'status',
        'starts_at',
        'ends_at',
        'registration_starts_at',
        'registration_ends_at',
        'max_participants',
        'registration_mode',
        'min_team_size',
        'max_team_size',
        'requires_coach',
    ];

    protected function casts(): array
    {
        return [
            'status' => CompetitionStatus::class,
            'registration_mode' => RegistrationMode::class,
            'requires_coach' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'registration_starts_at' => 'datetime',
            'registration_ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new OrganizationScope);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(CompetitionCategory::class)->orderBy('sort_order');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function allowsTeams(): bool
    {
        return $this->registration_mode->allowsTeams();
    }

    public function allowsIndividual(): bool
    {
        return $this->registration_mode->allowsIndividual();
    }

    public function isDraft(): bool
    {
        return $this->status === CompetitionStatus::Draft;
    }

    public function isPublished(): bool
    {
        return $this->status === CompetitionStatus::Published;
    }

    public function isActive(): bool
    {
        return $this->status === CompetitionStatus::Active;
    }

    public function isClosed(): bool
    {
        return $this->status === CompetitionStatus::Closed;
    }
}
