<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CategoryStatus;
use Database\Factories\CompetitionCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompetitionCategory extends Model
{
    /** @use HasFactory<CompetitionCategoryFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'competition_id',
        'name',
        'slug',
        'description',
        'status',
        'sort_order',
        'max_participants',
        'registration_ends_at',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'status' => CategoryStatus::class,
            'registration_ends_at' => 'datetime',
            'is_default' => 'boolean',
        ];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function isActive(): bool
    {
        return $this->status === CategoryStatus::Active;
    }

    public function isDefault(): bool
    {
        return $this->is_default;
    }
}
