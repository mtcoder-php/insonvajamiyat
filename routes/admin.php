<?php

use App\Enums\AdminSection;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SectionController;
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

// Sidebar bo'limlari. CRUD tayyor bo'lgan bo'lim shu ro'yxatdan chiqarilib,
// o'z controller'i bilan alohida ro'yxatdan o'tkaziladi (nomi o'zgarmaydi: admin.{key}.index).
foreach (AdminSection::cases() as $section) {
    Route::get($section->value, SectionController::class)
        ->defaults('section', $section->value)
        ->middleware('permission:'.$section->permission()->value)
        ->name($section->routeKey().'.index');
}
