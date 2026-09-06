<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\CompetitionOrganizationScope;
use Database\Factories\RubricFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rubric extends Model
{
    /** @use HasFactory<RubricFactory> */
    use HasFactory;

    protected $fillable = [
        'competition_id',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CompetitionOrganizationScope);
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(RubricCriterion::class)->orderBy('sort_order');
    }
}
