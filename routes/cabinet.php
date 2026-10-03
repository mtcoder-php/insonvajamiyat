<?php

use App\Enums\PermissionName;
use App\Http\Controllers\Articles\ArticleMessageController;
use App\Http\Controllers\Cabinet\AiStudioController;
use App\Http\Controllers\Cabinet\ArticleController;
use App\Http\Controllers\Cabinet\ArticleDraftFileController;
use App\Http\Controllers\Cabinet\ArticleFileController;
use App\Http\Controllers\Cabinet\ArticlePaymentController;
use App\Http\Controllers\Cabinet\ArticleProofController;
use App\Http\Controllers\Cabinet\ArticleRevisionController;
use App\Http\Controllers\Cabinet\ArticleSubmissionController;
use App\Http\Controllers\Cabinet\DashboardController;
use App\Http\Controllers\Cabinet\MessagesController;
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

// Xabarlar: tahririyat bilan yozishmalar va bildirishnomalar
Route::get('messages', MessagesController::class)->name('messages.index');

// AI Studio: imlo/uslub tekshiruvi, ilmiy tarjima, tahlil (oylik token limiti bilan)
Route::middleware('permission:'.PermissionName::AiUse->value)
    ->prefix('ai')
    ->name('ai.')
    ->group(function (): void {
        Route::get('/', [AiStudioController::class, 'index'])->name('index');
        Route::post('requests', [AiStudioController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('requests.store');
        Route::put('requests/{aiRequest:uuid}/proofread', [AiStudioController::class, 'proofread'])->name('requests.proofread');
        Route::get('requests/{aiRequest:uuid}/download', [AiStudioController::class, 'downloadProofread'])->name('requests.download');
        Route::post('translations/{translation:uuid}/versions', [AiStudioController::class, 'storeVersion'])
            ->middleware('throttle:30,1')
            ->name('translations.versions');
        Route::get('translations/{translation:uuid}/download', [AiStudioController::class, 'download'])->name('translations.download');
    });

Route::prefix('articles')->name('articles.')->group(function (): void {
    Route::get('/', [ArticleController::class, 'index'])->name('index');

    // Yangi maqola yuborish (7 bosqichli forma; 1-bosqichdan keyin qoralama)
    Route::get('create', [ArticleSubmissionController::class, 'create'])->name('create');
    Route::post('/', [ArticleSubmissionController::class, 'store'])->middleware('throttle:20,1')->name('store');

    Route::get('{article:uuid}', [ArticleController::class, 'show'])->name('show');
    Route::delete('{article:uuid}', [ArticleSubmissionController::class, 'destroy'])->name('destroy');
    Route::post('{article:uuid}/withdraw', [ArticleController::class, 'withdraw'])
        ->middleware('throttle:10,1')
        ->name('withdraw');

    Route::get('{article:uuid}/edit', [ArticleSubmissionController::class, 'edit'])->name('edit');
    Route::put('{article:uuid}/details', [ArticleSubmissionController::class, 'updateDetails'])->name('details.update');
    Route::put('{article:uuid}/authors', [ArticleSubmissionController::class, 'updateAuthors'])->name('authors.update');
    Route::put('{article:uuid}/abstract', [ArticleSubmissionController::class, 'updateAbstract'])->name('abstract.update');
    Route::put('{article:uuid}/keywords', [ArticleSubmissionController::class, 'updateKeywords'])->name('keywords.update');
    Route::post('{article:uuid}/submit', [ArticleSubmissionController::class, 'submit'])
        ->middleware('throttle:10,1')
        ->name('submit');

    // Nashr to'lovi: Click / Payme sahifasiga yo'naltirish
    Route::post('{article:uuid}/pay', [ArticlePaymentController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('pay');

    // Tuzatish sikli: tuzatilgan versiya (RevisionRequired → Resubmitted)
    Route::post('{article:uuid}/revision', [ArticleRevisionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('revision.store');

    // Korrektura (yakuniy PDF): muallif tasdiqlaydi yoki tuzatish so'raydi
    Route::post('{article:uuid}/proof/approve', [ArticleProofController::class, 'approve'])
        ->middleware('throttle:10,1')
        ->name('proof.approve');
    Route::post('{article:uuid}/proof/changes', [ArticleProofController::class, 'changes'])
        ->middleware('throttle:10,1')
        ->name('proof.changes');

    // Muallif ↔ tahririyat yozishmasi (fayl havolasi xodimlar uchun ham shu)
    Route::post('{article:uuid}/messages', [ArticleMessageController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('messages.store');
    Route::get('{article:uuid}/messages/{message}/attachment', [ArticleMessageController::class, 'attachment'])
        ->scopeBindings()
        ->name('messages.attachment');

    Route::post('{article:uuid}/files', [ArticleDraftFileController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('files.store');
    Route::get('{article:uuid}/files/{file:uuid}', ArticleFileController::class)
        ->scopeBindings()
        ->name('files.download');
    Route::delete('{article:uuid}/files/{file:uuid}', [ArticleDraftFileController::class, 'destroy'])
        ->scopeBindings()
        ->name('files.destroy');
});
