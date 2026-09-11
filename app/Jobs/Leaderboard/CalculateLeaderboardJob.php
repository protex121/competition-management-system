<?php

declare(strict_types=1);

namespace App\Jobs\Leaderboard;

use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Scopes\CompetitionOrganizationScope;
use App\Services\Leaderboard\CalculateCategoryLeaderboardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateLeaderboardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Competition $competition,
    ) {}

    public function handle(CalculateCategoryLeaderboardService $service): void
    {
        $categories = CompetitionCategory::withoutGlobalScope(CompetitionOrganizationScope::class)
            ->where('competition_id', $this->competition->id)
            ->get();

        foreach ($categories as $category) {
            $service->execute($category);
        }
    }
}
