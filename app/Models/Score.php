<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\ScoreOrganizationScope;
use Database\Factories\ScoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    /** @use HasFactory<ScoreFactory> */
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'rubric_criterion_id',
        'judge_id',
        'score',
        'comment',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new ScoreOrganizationScope);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(RubricCriterion::class, 'rubric_criterion_id');
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'judge_id');
    }
}
