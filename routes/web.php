<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
require __DIR__.'/events.php';
