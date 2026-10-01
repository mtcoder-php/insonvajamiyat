<?php

use App\Enums\AdminSection;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\Users\UserAvatarController;
use App\Http\Controllers\Admin\Users\UserController;
use App\Http\Controllers\Admin\Users\UserPasswordController;
use App\Http\Controllers\Admin\Users\UserStatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin panel — TZ 4.2
|--------------------------------------------------------------------------
| Prefiks: /admin, nom: admin.*
| Middleware (bootstrap/app.php): web, auth, verified, staff
| Har bir bo'lim o'z ruxsati bilan himoyalanadi (App\Enums\AdminSection::permission()).
| Sahifalar: resources/js/pages/admin/* (AdminLayout)
*/

Route::get('/', DashboardController::class)->name('dashboard');

/*
| Foydalanuvchilar (TZ 4.2.4)
*/
Route::middleware('permission:'.AdminSection::Users->permission()->value)
    ->prefix('users')
    ->name('users.')
    ->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('{user}', [UserController::class, 'show'])->name('show')->withTrashed();
        Route::get('{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('{user}', [UserController::class, 'update'])->name('update');
        Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy');
        Route::post('{user}/restore', [UserController::class, 'restore'])->name('restore')->withTrashed();

        Route::post('{user}/block', [UserStatusController::class, 'store'])->name('block');
        Route::delete('{user}/block', [UserStatusController::class, 'destroy'])->name('unblock');

        Route::put('{user}/password', [UserPasswordController::class, 'update'])->name('password.update');
        Route::post('{user}/password/reset', [UserPasswordController::class, 'reset'])
            ->middleware('throttle:6,1')
            ->name('password.reset');

        Route::post('{user}/avatar', [UserAvatarController::class, 'store'])->name('avatar.store');
        Route::delete('{user}/avatar', [UserAvatarController::class, 'destroy'])->name('avatar.destroy');
    });

// Hali ishlab chiqilmagan bo'limlar — vaqtinchalik sahifa (admin/Section).
// Bo'lim tayyor bo'lgach AdminSection::isReady() true qaytaradi va yuqorida
// o'z controller'i bilan ro'yxatdan o'tadi (route nomi o'zgarmaydi: admin.{key}.index).
foreach (AdminSection::cases() as $section) {
    if ($section->isReady()) {
        continue;
    }

    Route::get($section->value, SectionController::class)
        ->defaults('section', $section->value)
        ->middleware('permission:'.$section->permission()->value)
        ->name($section->routeKey().'.index');
}
