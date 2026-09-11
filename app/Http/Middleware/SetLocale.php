<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const SUPPORTED = ['en', 'id'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        if (! in_array($locale, self::SUPPORTED, true)) {
            return $next($request);
        }

        App::setLocale($locale);
        URL::defaults(['locale' => $locale]);

        if ($request->cookie('app_locale') !== $locale) {
            Cookie::queue('app_locale', $locale, 60 * 24 * 365);
        }

        // The {locale} segment is only there to build/match the URL. Left in
        // the route's parameter bag, it becomes an extra untyped value that
        // throws off Laravel's positional splicing of route-model-bound
        // controller parameters (e.g. a Team $team argument silently
        // receiving "en" instead of the bound model). Drop it once consumed
        // so every controller signature stays exactly as it was.
        $request->route()?->forgetParameter('locale');

        return $next($request);
    }
}
