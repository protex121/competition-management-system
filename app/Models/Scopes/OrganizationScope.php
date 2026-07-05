<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class OrganizationScope implements Scope
{
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

        if ($user->organization_id !== null) {
            $builder->where(
                $model->getTable().'.organization_id',
                $user->organization_id,
            );
        }
    }
}
