<?php

declare(strict_types=1);

namespace App\Services\Competition;

use App\Enums\CompetitionStatus;
use App\Enums\TeamMemberStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;

class BuildPublicParticipationCtaService
{
    /**
     * @return array{
     *     visible: bool,
     *     status?: string,
     *     message?: string,
     *     login_url?: string,
     *     register_url?: string,
     *     action_url?: string,
     *     action_label?: string,
     * }
     */
    public function execute(?User $user, Competition $competition, Organization $organization): array
    {
        if ($competition->isClosed()) {
            return [
                'visible' => true,
                'status' => 'closed',
                'message' => 'Registration for this competition has ended.',
            ];
        }

        if (! in_array($competition->status, [CompetitionStatus::Published, CompetitionStatus::Active], true)) {
            return ['visible' => false];
        }

        if ($this->registrationHasEnded($competition)) {
            return [
                'visible' => true,
                'status' => 'registration_closed',
                'message' => 'Registration is closed for this competition.',
            ];
        }

        if ($this->registrationHasNotStarted($competition)) {
            return [
                'visible' => true,
                'status' => 'registration_not_open',
                'message' => 'Registration has not opened yet.',
            ];
        }

        if ($user === null) {
            return [
                'visible' => true,
                'status' => 'guest',
                'message' => 'Log in or create an account to participate.',
                'login_url' => route('login'),
                'register_url' => route('register'),
            ];
        }

        if ($user->organization_id !== $organization->id) {
            return [
                'visible' => true,
                'status' => 'wrong_organization',
                'message' => 'You must belong to '.$organization->name.' to participate in this competition.',
            ];
        }

        if ($user->role !== UserRole::Participant) {
            return ['visible' => false];
        }

        if ($competition->allowsTeams()) {
            $team = Team::withoutGlobalScopes()
                ->where('competition_id', $competition->id)
                ->whereHas('members', function ($query) use ($user): void {
                    $query
                        ->where('user_id', $user->id)
                        ->where('status', TeamMemberStatus::Active);
                })
                ->first(['id']);

            if ($team !== null) {
                return [
                    'visible' => true,
                    'status' => 'view_team',
                    'message' => 'You are already on a team for this competition.',
                    'action_url' => route('teams.show', $team),
                    'action_label' => 'View your team',
                ];
            }

            return [
                'visible' => true,
                'status' => 'join_team',
                'message' => 'Join or create a team to participate.',
                'action_url' => route('competitions.teams.index', $competition),
                'action_label' => 'Join or create a team',
            ];
        }

        return [
            'visible' => true,
            'status' => 'browse',
            'message' => 'Browse open competitions to get started.',
            'action_url' => route('participant.competitions.index'),
            'action_label' => 'Browse competitions',
        ];
    }

    private function registrationHasEnded(Competition $competition): bool
    {
        return $competition->registration_ends_at !== null
            && now()->gt($competition->registration_ends_at);
    }

    private function registrationHasNotStarted(Competition $competition): bool
    {
        return $competition->registration_starts_at !== null
            && now()->lt($competition->registration_starts_at);
    }
}
