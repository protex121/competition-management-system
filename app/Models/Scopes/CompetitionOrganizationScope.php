<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Restricts queries to rows whose parent competition belongs to the actor's organization.
 */
class CompetitionOrganizationScope implements Scope
{
    public function __construct(
        private readonly string $competitionForeignKey = 'competition_id',
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
        $foreignKey = $this->competitionForeignKey;

        $builder->whereExists(function ($query) use ($table, $foreignKey, $user): void {
            $query->selectRaw('1')
                ->from('competitions')
                ->whereColumn('competitions.id', "{$table}.{$foreignKey}")
                ->where('competitions.organization_id', $user->organization_id)
                ->whereNull('competitions.deleted_at');
        });
    }
}
