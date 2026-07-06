<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\ParticipantProfile;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Policies\Competition\CompetitionCategoryPolicy;
use App\Policies\Competition\CompetitionPolicy;
use App\Policies\Identity\UserPolicy;
use App\Policies\Team\ParticipantProfilePolicy;
use App\Policies\Team\TeamInvitationPolicy;
use App\Policies\Team\TeamPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Competition::class, CompetitionPolicy::class);
        Gate::policy(CompetitionCategory::class, CompetitionCategoryPolicy::class);
        Gate::policy(ParticipantProfile::class, ParticipantProfilePolicy::class);
        Gate::policy(Team::class, TeamPolicy::class);
        Gate::policy(TeamInvitation::class, TeamInvitationPolicy::class);
    }
}
