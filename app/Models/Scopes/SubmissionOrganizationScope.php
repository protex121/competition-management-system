<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Restricts queries to rows whose parent registration's category's competition
 * belongs to the actor's organization.
 */
class SubmissionOrganizationScope implements Scope
{
    public function __construct(
        private readonly string $registrationForeignKey = 'registration_id',
    ) {}

    public function apply(Builder $builder, Model $model): void
    {
        if (app()->bound('background_mode') && app('background_mode') === true) {
            return;
        }

        $user = Auth::user();

        if ($user === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($user->organization_id === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $table = $model->getTable();
        $foreignKey = $this->registrationForeignKey;

        $builder->whereExists(function ($query) use ($table, $foreignKey, $user): void {
            $query->selectRaw('1')
                ->from('registrations')
                ->join('competition_categories', 'competition_categories.id', '=', 'registrations.competition_category_id')
                ->join('competitions', 'competitions.id', '=', 'competition_categories.competition_id')
                ->whereColumn('registrations.id', "{$table}.{$foreignKey}")
                ->where('competitions.organization_id', $user->organization_id)
                ->whereNull('competition_categories.deleted_at')
                ->whereNull('competitions.deleted_at');
        });
    }
}
