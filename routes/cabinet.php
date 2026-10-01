<?php

use App\Http\Controllers\Cabinet\ArticleController;
use App\Http\Controllers\Cabinet\ArticleFileController;
use App\Http\Controllers\Cabinet\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Muallif kabineti — TZ 4.1.3
|--------------------------------------------------------------------------
| Prefiks: /cabinet, nom: cabinet.*
| Middleware (bootstrap/app.php): web, auth, verified
| Sahifalar: resources/js/pages/cabinet/* (CabinetLayout)
|
| Maqola URL'larida uuid ishlatiladi (slug faqat nashr etilgan maqolalar uchun).
*/

Route::get('/', DashboardController::class)->name('dashboard');

Route::prefix('articles')->name('articles.')->group(function (): void {
    Route::get('/', [ArticleController::class, 'index'])->name('index');
    Route::get('create', [ArticleController::class, 'create'])->name('create');
    Route::get('{article:uuid}', [ArticleController::class, 'show'])->name('show');
    Route::post('{article:uuid}/withdraw', [ArticleController::class, 'withdraw'])
        ->middleware('throttle:10,1')
        ->name('withdraw');
    Route::get('{article:uuid}/files/{file:uuid}', ArticleFileController::class)
        ->scopeBindings()
        ->name('files.download');
});
