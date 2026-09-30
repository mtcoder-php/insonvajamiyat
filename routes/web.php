<?php

use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Web\ArticleController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\IssueController;
use App\Http\Controllers\Web\LocaleController;
use App\Http\Controllers\Web\NewsletterSubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web (Public) qism — TZ 4.1.1
|--------------------------------------------------------------------------
| Barcha tashrif buyuruvchilar uchun ochiq sahifalar. Sahifalar
| resources/js/pages/web/* da joylashgan va WebLayout bilan chiqadi.
| Muallif kabineti: routes/cabinet.php, admin panel: routes/admin.php
*/

Route::get('/', HomeController::class)->name('home');
Route::inertia('about', 'web/About')->name('about');
Route::inertia('articles', 'web/articles/Index')->name('articles.index');
Route::get('articles/{article}', [ArticleController::class, 'show'])->name('articles.show');
Route::inertia('issues', 'web/issues/Index')->name('issues.index');
Route::get('issues/{issue}', [IssueController::class, 'show'])->name('issues.show');
Route::inertia('guidelines', 'web/Guidelines')->name('guidelines');
Route::inertia('contact', 'web/Contact')->name('contact');

// Sayt tili (UZ / RU / EN)
Route::post('locale', [LocaleController::class, 'update'])
    ->middleware('throttle:20,1')
    ->name('locale.update');

// Footer: yangiliklarga obuna (spamdan himoya — daqiqasiga 5 ta so'rov)
Route::post('newsletter', [NewsletterSubscriptionController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('newsletter.subscribe');

// Fortify "home": login / 2FA / email tasdiqlashdan keyin roliga qarab yo'naltiradi
Route::get('dashboard', DashboardRedirectController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
