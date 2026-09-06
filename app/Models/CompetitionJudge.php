<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CompetitionJudgeStatus;
use App\Models\Scopes\CompetitionOrganizationScope;
use Database\Factories\CompetitionJudgeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitionJudge extends Model
{
    /** @use HasFactory<CompetitionJudgeFactory> */
    use HasFactory;

    protected $fillable = [
        'competition_id',
        'user_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => CompetitionJudgeStatus::class,
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === CompetitionJudgeStatus::Active;
    }
}
