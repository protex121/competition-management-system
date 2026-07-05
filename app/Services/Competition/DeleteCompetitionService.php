<?php

declare(strict_types=1);

namespace App\Services\Competition;

use App\Models\Competition;

class DeleteCompetitionService
{
    public function execute(Competition $competition): void
    {
        $competition->delete();
    }
}
