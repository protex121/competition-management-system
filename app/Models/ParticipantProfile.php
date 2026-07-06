<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ParticipantProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipantProfile extends Model
{
    /** @use HasFactory<ParticipantProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bio',
        'phone',
        'institution',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
