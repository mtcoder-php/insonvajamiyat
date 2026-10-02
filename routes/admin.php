<?php

use App\Enums\AdminSection;
use App\Enums\PermissionName;
use App\Http\Controllers\Admin\Articles\EditorialController;
use App\Http\Controllers\Admin\Articles\ReviewerAssignmentController;
use App\Http\Controllers\Admin\Audit\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Issues\IssueArticleController;
use App\Http\Controllers\Admin\Issues\IssueController;
use App\Http\Controllers\Admin\Payments\PaymentController;
use App\Http\Controllers\Admin\Production\ProductionController;
use App\Http\Controllers\Admin\Reports\ReportController;
use App\Http\Controllers\Admin\Reviews\ReviewController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\Users\UserAvatarController;
use App\Http\Controllers\Admin\Users\UserController;
use App\Http\Controllers\Admin\Users\UserPasswordController;
use App\Http\Controllers\Admin\Users\UserStatusController;
use App\Http\Controllers\Articles\ArticleMessageController;
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

/*
| Maqolalar — muharrir ish joyi (TZ 4.2.2)
*/
Route::middleware('permission:'.AdminSection::Articles->permission()->value)
    ->prefix('articles')
    ->name('articles.')
    ->group(function () {
        Route::get('/', [EditorialController::class, 'index'])->name('index');
        Route::post('{article:uuid}/notes', [EditorialController::class, 'addNote'])->name('notes');

        Route::middleware('permission:'.PermissionName::ArticlesDecide->value)->group(function () {
            Route::post('{article:uuid}/start-review', [EditorialController::class, 'startReview'])->name('start-review');
            Route::post('{article:uuid}/decision', [EditorialController::class, 'decide'])->name('decision');
            Route::put('{article:uuid}/editor', [EditorialController::class, 'assignEditor'])->name('editor');
        });

        Route::post('{article:uuid}/messages', [ArticleMessageController::class, 'store'])
            ->middleware(['permission:'.PermissionName::ArticlesMessageAuthor->value, 'throttle:30,1'])
            ->name('messages.store');

        Route::middleware('permission:'.PermissionName::ArticlesAssignReviewer->value)->group(function () {
            Route::post('{article:uuid}/reviewers', [ReviewerAssignmentController::class, 'store'])->name('reviewers.store');
            Route::delete('{article:uuid}/reviews/{review}', [ReviewerAssignmentController::class, 'destroy'])->name('reviews.destroy');
        });
    });

/*
| Nashr jarayoni (admin publisher page.png): maketlash, yakuniy PDF, tekshiruv, tasdiq
*/
Route::middleware('permission:'.AdminSection::Production->permission()->value)
    ->prefix('production')
    ->name('production.')
    ->group(function () {
        Route::get('/', [ProductionController::class, 'index'])->name('index');
        Route::get('{article:uuid}', [ProductionController::class, 'show'])->name('show');
        Route::get('{article:uuid}/files/{file:uuid}', [ProductionController::class, 'file'])
            ->scopeBindings()
            ->name('files');
        Route::post('{article:uuid}/start', [ProductionController::class, 'start'])->name('start');
        Route::post('{article:uuid}/final-pdf', [ProductionController::class, 'uploadFinalPdf'])->name('final-pdf');
        Route::put('{article:uuid}/metadata', [ProductionController::class, 'metadata'])->name('metadata');
        Route::put('{article:uuid}/format', [ProductionController::class, 'format'])->name('format');
        Route::post('{article:uuid}/cancel', [ProductionController::class, 'cancel'])->name('cancel');
        Route::post('{article:uuid}/notes', [ProductionController::class, 'addNote'])->name('notes');

        // Bosh muharrir tasdig'i
        Route::middleware('permission:'.PermissionName::IssuesPublish->value)->group(function () {
            Route::post('{article:uuid}/approve', [ProductionController::class, 'approve'])->name('approve');
            Route::post('{article:uuid}/waive-proof', [ProductionController::class, 'waiveProof'])->name('waive-proof');
            Route::post('{article:uuid}/revoke', [ProductionController::class, 'revoke'])->name('revoke');
            Route::post('{article:uuid}/publish', [ProductionController::class, 'publish'])->name('publish');
        });
    });

/*
| Jurnallar (TZ 4.2.3): sonlar, tarkib (tartib, rukn, sahifalar), muqova, PDF, mundarija
*/
Route::middleware('permission:'.AdminSection::Issues->permission()->value)
    ->prefix('issues')
    ->name('issues.')
    ->group(function () {
        Route::get('/', [IssueController::class, 'index'])->name('index');
        Route::post('/', [IssueController::class, 'store'])->name('store');
        Route::get('{issue}', [IssueController::class, 'show'])->name('show');
        Route::put('{issue}', [IssueController::class, 'update'])->name('update');
        Route::delete('{issue}', [IssueController::class, 'destroy'])->name('destroy');
        Route::get('{issue}/toc', [IssueController::class, 'toc'])->name('toc');
        Route::post('{issue}/publish', [IssueController::class, 'publish'])
            ->middleware('permission:'.PermissionName::IssuesPublish->value)
            ->name('publish');
        Route::post('{issue}/files', [IssueController::class, 'storeFile'])->name('files.store');
        Route::delete('{issue}/files/{type}', [IssueController::class, 'destroyFile'])
            ->whereIn('type', ['cover', 'pdf', 'toc'])
            ->name('files.destroy');

        Route::post('{issue}/articles', [IssueArticleController::class, 'store'])->name('articles.store');
        Route::put('{issue}/articles/order', [IssueArticleController::class, 'reorder'])->name('articles.reorder');
        Route::post('{issue}/articles/paginate', [IssueArticleController::class, 'paginate'])->name('articles.paginate');
        // Maqola songa issue_articles orqali bog'langan — tegishlilik IssueService'da tekshiriladi
        Route::put('{issue}/articles/{article:uuid}', [IssueArticleController::class, 'update'])
            ->withoutScopedBindings()
            ->name('articles.update');
        Route::delete('{issue}/articles/{article:uuid}', [IssueArticleController::class, 'destroy'])
            ->withoutScopedBindings()
            ->name('articles.destroy');
    });

/*
| Taqrizlarim — taqrizchi ish joyi (TZ 4.2.3)
*/
Route::prefix('reviews')->name('reviews.')->group(function () {
    // Taqriz fayli: taqrizchi yoki maqolalarni ko'ra oladigan xodim (controller'da tekshiriladi)
    Route::get('{review}/attachment', [ReviewController::class, 'attachment'])->name('attachment');

    Route::middleware('permission:'.PermissionName::ReviewsSubmit->value)->group(function () {
        Route::get('/', [ReviewController::class, 'index'])->name('index');
        Route::get('{review}', [ReviewController::class, 'show'])->name('show');
        Route::post('{review}/accept', [ReviewController::class, 'accept'])->name('accept');
        Route::post('{review}/decline', [ReviewController::class, 'decline'])->name('decline');
        Route::put('{review}', [ReviewController::class, 'update'])->name('update');
        // Fayl maqolaga tegishli (Review::files() yo'q) — tegishlilik controllerda tekshiriladi
        Route::get('{review}/files/{file:uuid}', [ReviewController::class, 'file'])
            ->withoutScopedBindings()
            ->name('files');
    });
});

/*
| To'lovlar (TZ 4.2.8): ro'yxat, qo'lda tasdiqlash, to'lovdan ozod qilish
*/
Route::middleware('permission:'.AdminSection::Payments->permission()->value)
    ->prefix('payments')
    ->name('payments.')
    ->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('{payment:uuid}/proof', [PaymentController::class, 'proof'])->name('proof');

        Route::middleware('permission:'.PermissionName::PaymentsConfirmManually->value)->group(function () {
            Route::post('articles/{article:uuid}/confirm', [PaymentController::class, 'confirm'])->name('confirm');
            Route::post('articles/{article:uuid}/waive', [PaymentController::class, 'waive'])->name('waive');
        });
    });

/*
| Statistika va hisobotlar (super admin analistic page.png): davr bo'yicha ko'rsatkichlar,
| taqrizchilar samaradorligi, CSV (Excel) eksport va chop etiladigan umumiy hisobot
*/
Route::middleware('permission:'.AdminSection::Reports->permission()->value)
    ->prefix('reports')
    ->name('reports.')
    ->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('print', [ReportController::class, 'print'])->name('print');
        Route::get('export/{type}', [ReportController::class, 'export'])
            ->whereIn('type', ['articles', 'payments', 'reviewers', 'authors'])
            ->middleware('throttle:20,1')
            ->name('export');
    });

/*
| Audit log (TZ 4.2.9): faqat ko'rish va eksport
*/
Route::middleware('permission:'.AdminSection::Audit->permission()->value)
    ->prefix(AdminSection::Audit->value)
    ->name('audit.')
    ->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('export', [AuditLogController::class, 'export'])
            ->middleware('throttle:10,1')
            ->name('export');
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
