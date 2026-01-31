<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\WhitelistedEmailController;
use App\Http\Controllers\Api\RegenerationController;
use App\Http\Controllers\Api\RegenerationStatusController;
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

Route::group(['prefix' => 'auth/google'], function () {
    Route::get('redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
});

Route::group(['middleware' => ['auth', 'verified']], function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::group(['prefix' => 'project'], function () {
        Route::get('{project}', [ProjectController::class, 'show'])->name('project.show');
        Route::get('{project}/day/{day}', DayController::class)->name('project.day.show');
    });

    Route::group(['prefix' => 'api/regeneration'], function () {
        Route::post('project/{project}/single', [RegenerationController::class, 'single'])->name('api.regeneration.single');
        Route::post('project/{project}/day/{day}', [RegenerationController::class, 'day'])->name('api.regeneration.day');
        Route::post('project/{project}/column', [RegenerationController::class, 'column'])->name('api.regeneration.column');
        Route::post('project/{project}', [RegenerationController::class, 'project'])->name('api.regeneration.project');
        Route::get('project/{project}/status', [RegenerationStatusController::class, 'status'])->name('api.regeneration.status');
    });
});

Route::group(['middleware' => 'auth'], function () {
    Route::redirect('settings', '/settings/profile');

    Route::group(['prefix' => 'settings'], function () {
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::get('password', [PasswordController::class, 'edit'])->name('user-password.edit');
        Route::put('password', [PasswordController::class, 'update'])
            ->middleware('throttle:6,1')
            ->name('user-password.update');

        Route::get('appearance', function () {
            return Inertia::render('settings/appearance');
        })->name('appearance.edit');

        Route::get('two-factor', [TwoFactorAuthenticationController::class, 'show'])->name('two-factor.show');
    });

    Route::group(['prefix' => 'admin'], function () {
        Route::group(['prefix' => 'users'], function () {
            Route::get('/', [UserManagementController::class, 'index'])->name('admin.users.index');
            Route::post('/', [UserManagementController::class, 'store'])->name('admin.users.store');
            Route::delete('{user}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');
        });

        Route::group(['prefix' => 'whitelisted-emails'], function () {
            Route::get('/', [WhitelistedEmailController::class, 'index'])->name('admin.whitelisted-emails.index');
            Route::post('/', [WhitelistedEmailController::class, 'store'])->name('admin.whitelisted-emails.store');
            Route::delete('{whitelistedEmail}', [WhitelistedEmailController::class, 'destroy'])->name('admin.whitelisted-emails.destroy');
        });
    });
});
