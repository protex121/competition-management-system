<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::prefix('{locale}')->whereIn('locale', SetLocale::SUPPORTED)->group(function () {
    Route::get('/', function () {
        return Inertia::render('Welcome');
    })->name('home');

    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware(['auth', 'verified', 'active'])->name('dashboard');

    require __DIR__.'/settings.php';
    require __DIR__.'/auth.php';
    require __DIR__.'/users.php';
    require __DIR__.'/competitions.php';
    require __DIR__.'/participant.php';
    require __DIR__.'/teams.php';
    require __DIR__.'/registrations.php';
    require __DIR__.'/submissions.php';
    require __DIR__.'/judging.php';
    require __DIR__.'/leaderboard.php';
    require __DIR__.'/events.php';
});

// Bare/unprefixed URLs (including the domain root) don't match anything inside
// the {locale} group above, so they fall through to here: redirect to the same
// path with the cookie-remembered (or default) locale prepended. A path that
// already starts with a valid locale segment but still didn't match a real
// route is a genuine 404, not a redirect loop waiting to happen.
Route::fallback(function () {
    $path = trim(request()->path(), '/');
    $firstSegment = explode('/', $path)[0] ?? '';

    // A supported locale that still 404'd is a genuinely missing route. An
    // unsupported-but-locale-shaped segment (e.g. "fr") is almost certainly
    // someone trying a locale prefix, not a real top-level path in this app
    // — 404 immediately instead of redirect-prepending another locale in
    // front of it.
    if (in_array($firstSegment, SetLocale::SUPPORTED, true) || preg_match('/^[a-z]{2}$/', $firstSegment) === 1) {
        abort(404);
    }

    $locale = request()->cookie('app_locale');

    if (! in_array($locale, SetLocale::SUPPORTED, true)) {
        $locale = config('app.locale', 'en');
    }

    return redirect($path === '' ? "/{$locale}" : "/{$locale}/{$path}");
});
