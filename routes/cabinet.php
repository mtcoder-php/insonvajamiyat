<?php

use App\Http\Controllers\Cabinet\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Muallif kabineti — TZ 4.1.3
|--------------------------------------------------------------------------
| Prefiks: /cabinet, nom: cabinet.*
| Middleware (bootstrap/app.php): web, auth, verified
| Sahifalar: resources/js/pages/cabinet/* (CabinetLayout)
*/

Route::get('/', DashboardController::class)->name('dashboard');
