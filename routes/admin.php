<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin panel — TZ 4.2
|--------------------------------------------------------------------------
| Prefiks: /admin, nom: admin.*
| Middleware (bootstrap/app.php): web, auth, verified, staff
| Har bir bo'lim qo'shimcha ravishda ruxsat bilan himoyalanadi, masalan:
|   Route::middleware('permission:users.manage')->group(...)
| Sahifalar: resources/js/pages/admin/* (AdminLayout)
*/

Route::get('/', DashboardController::class)->name('dashboard');
