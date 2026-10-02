<?php

use App\Http\Controllers\Payments\ClickController;
use App\Http\Controllers\Payments\PaymeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| To'lov tizimlari webhook'lari — /payments/*
|--------------------------------------------------------------------------
| bootstrap/app.php da "web" guruhisiz ulanadi: sessiya, cookie va CSRF yo'q.
| Xavfsizlik: Click — md5 imzo (SECRET_KEY), Payme — Basic avtorizatsiya (KEY).
*/

Route::post('click/prepare', [ClickController::class, 'prepare'])->name('click.prepare');
Route::post('click/complete', [ClickController::class, 'complete'])->name('click.complete');
Route::post('payme', PaymeController::class)->name('payme');
