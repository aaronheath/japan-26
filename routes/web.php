<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\WhitelistedEmailController;
use App\Http\Controllers\DayController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
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

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance.edit');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
});

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('users', [UserManagementController::class, 'index'])->name('admin.users.index');
    Route::post('users', [UserManagementController::class, 'store'])->name('admin.users.store');
    Route::delete('users/{user}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');

    Route::get('whitelisted-emails', [WhitelistedEmailController::class, 'index'])->name('admin.whitelisted-emails.index');
    Route::post('whitelisted-emails', [WhitelistedEmailController::class, 'store'])->name('admin.whitelisted-emails.store');
    Route::delete('whitelisted-emails/{whitelistedEmail}', [WhitelistedEmailController::class, 'destroy'])->name('admin.whitelisted-emails.destroy');
});
