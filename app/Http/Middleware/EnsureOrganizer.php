<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || (! $user->isOrganizer() && ! $user->isSuperAdmin())) {
            abort(403, 'You are not authorized to manage users.');
        }

        return $next($request);
    }
}
