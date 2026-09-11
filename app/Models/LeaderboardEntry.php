<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\RegistrationOrganizationScope;
use Database\Factories\LeaderboardEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardEntry extends Model
{
    /** @use HasFactory<LeaderboardEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'competition_category_id',
        'submission_id',
        'aggregate_score',
        'judge_count',
        'rank',
    ];

    protected function casts(): array
    {
        return [
            'aggregate_score' => 'decimal:2',
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

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
