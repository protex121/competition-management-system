<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\CompetitionJudge;
use App\Models\ParticipantProfile;
use App\Models\Registration;
use App\Models\Rubric;
use App\Models\Score;
use App\Models\Submission;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Policies\Competition\CompetitionCategoryPolicy;
use App\Policies\Competition\CompetitionPolicy;
use App\Policies\Identity\UserPolicy;
use App\Policies\Judging\CompetitionJudgePolicy;
use App\Policies\Judging\RubricPolicy;
use App\Policies\Judging\ScorePolicy;
use App\Policies\Registration\RegistrationPolicy;
use App\Policies\Submission\SubmissionPolicy;
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
        Gate::policy(Registration::class, RegistrationPolicy::class);
        Gate::policy(Submission::class, SubmissionPolicy::class);
        Gate::policy(CompetitionJudge::class, CompetitionJudgePolicy::class);
        Gate::policy(Rubric::class, RubricPolicy::class);
        Gate::policy(Score::class, ScorePolicy::class);
    }
}
