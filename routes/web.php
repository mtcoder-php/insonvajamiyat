<?php

use App\Http\Controllers\DashboardRedirectController;
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

Route::inertia('/', 'web/Home')->name('home');
Route::inertia('about', 'web/About')->name('about');
Route::inertia('articles', 'web/articles/Index')->name('articles.index');
Route::inertia('issues', 'web/issues/Index')->name('issues.index');
Route::inertia('guidelines', 'web/Guidelines')->name('guidelines');
Route::inertia('contact', 'web/Contact')->name('contact');

// Footer: yangiliklarga obuna (spamdan himoya — daqiqasiga 5 ta so'rov)
Route::post('newsletter', [NewsletterSubscriptionController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('newsletter.subscribe');

// Fortify "home": login / 2FA / email tasdiqlashdan keyin roliga qarab yo'naltiradi
Route::get('dashboard', DashboardRedirectController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
