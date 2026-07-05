<?php

declare(strict_types=1);

namespace App\Events\Competition;

use App\Models\Competition;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompetitionPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Competition $competition,
    ) {}
}
