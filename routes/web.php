<?php

use App\Http\Controllers\DayController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
    ->name('google.redirect');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('google.callback');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('/project/{project}', [ProjectController::class, 'show'])
        ->name('project.show');

    Route::get('/project/{project}/day/{day}', DayController::class)
        ->name('project.day.show');
});

require __DIR__.'/settings.php';
