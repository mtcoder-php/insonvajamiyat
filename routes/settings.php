<?php

use App\Http\Controllers\Settings\ProfileAvatarController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\SocialAccountController;
use App\Http\Middleware\RequirePasswordIfSet;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::post('settings/avatar', [ProfileAvatarController::class, 'store'])->name('profile.avatar.store');
    Route::delete('settings/avatar', [ProfileAvatarController::class, 'destroy'])->name('profile.avatar.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePasswordIfSet::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    // Google / ORCID akkauntini uzish (bog'lash — /auth/{provider}/redirect)
    Route::delete('settings/social/{provider}', [SocialAccountController::class, 'destroy'])
        ->middleware('throttle:10,1')
        ->name('social.destroy');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
});
