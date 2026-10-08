<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\SocialRegisterController;
use Illuminate\Support\Facades\Route;

/*
| Google / ORCID orqali kirish (config/services.php → google, orcid).
| Provayder sozlanmagan bo'lsa — 404. Kirgan foydalanuvchi uchun — akkauntni bog'lash.
*/
Route::middleware('throttle:20,1')->group(function (): void {
    Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
    Route::get('auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');
});

// Birinchi marta kirgan muallif: ism-familiya, telefon (va kerak bo'lsa email)
Route::middleware('guest')->group(function (): void {
    Route::get('auth/register/complete', [SocialRegisterController::class, 'show'])->name('social.register');
    Route::post('auth/register/complete', [SocialRegisterController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('social.register.store');
});
