<?php

use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Notifications\NotificationController;
use App\Http\Controllers\Web\ArticleCatalogController;
use App\Http\Controllers\Web\ArticleController;
use App\Http\Controllers\Web\ArticlePdfController;
use App\Http\Controllers\Web\EventController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\IssueController;
use App\Http\Controllers\Web\LocaleController;
use App\Http\Controllers\Web\NewsController;
use App\Http\Controllers\Web\NewsletterSubscriptionController;
use App\Http\Controllers\Web\RobotsController;
use App\Http\Controllers\Web\SitemapController;
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
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('robots.txt', RobotsController::class)->name('robots');
Route::inertia('about', 'web/About')->name('about');
Route::get('articles', ArticleCatalogController::class)->name('articles.index');
Route::get('articles/{article}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('articles/{article}/pdf', ArticlePdfController::class)
    ->middleware('throttle:60,1')
    ->name('articles.pdf');
Route::get('issues', [IssueController::class, 'index'])->name('issues.index');
Route::get('issues/{issue}', [IssueController::class, 'show'])->name('issues.show');
Route::get('news', [NewsController::class, 'index'])->name('news.index');
Route::get('news/{post:slug}', [NewsController::class, 'show'])->name('news.show');
Route::get('events', [EventController::class, 'index'])->name('events.index');
Route::get('events/{event:slug}', [EventController::class, 'show'])->name('events.show');
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

// Bildirishnomalar (header'dagi qo'ng'iroqcha, kabinetdagi "Xabarlar")
Route::middleware(['auth', 'verified'])->prefix('notifications')->name('notifications.')->group(function (): void {
    Route::post('read-all', [NotificationController::class, 'readAll'])->name('read-all');
    Route::post('{notification}/read', [NotificationController::class, 'open'])
        ->whereUuid('notification')
        ->name('read');
});

require __DIR__.'/settings.php';
require __DIR__.'/social.php';
