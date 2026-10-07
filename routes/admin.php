<?php

use App\Enums\AdminSection;
use App\Enums\PermissionName;
use App\Http\Controllers\Admin\Ai\AiSettingsController;
use App\Http\Controllers\Admin\Ai\AiStudioController;
use App\Http\Controllers\Admin\Articles\EditorialController;
use App\Http\Controllers\Admin\Articles\ReviewerAssignmentController;
use App\Http\Controllers\Admin\Audit\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Issues\IssueArticleController;
use App\Http\Controllers\Admin\Issues\IssueController;
use App\Http\Controllers\Admin\Payments\PaymentController;
use App\Http\Controllers\Admin\People\AuthorController;
use App\Http\Controllers\Admin\People\ReviewerController;
use App\Http\Controllers\Admin\Production\ProductionController;
use App\Http\Controllers\Admin\Reports\ReportController;
use App\Http\Controllers\Admin\Reviews\ReviewController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\Settings\ArticleTypeController;
use App\Http\Controllers\Admin\Settings\BannerController;
use App\Http\Controllers\Admin\Settings\EventController;
use App\Http\Controllers\Admin\Settings\PartnerController;
use App\Http\Controllers\Admin\Settings\PostController;
use App\Http\Controllers\Admin\Settings\RecommendedBookController;
use App\Http\Controllers\Admin\Settings\SettingsController;
use App\Http\Controllers\Admin\Settings\SubjectController;
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
        Route::post('{article:uuid}/cover', [ProductionController::class, 'uploadCover'])->name('cover');
        Route::delete('{article:uuid}/cover', [ProductionController::class, 'removeCover'])->name('cover.destroy');
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
        Route::post('{issue}/pdf/build', [IssueController::class, 'buildPdf'])
            ->middleware('throttle:6,1')
            ->name('pdf.build');
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

/*
| AI Studio (super admin ai page.png): Proofreader, Translator, Analytics, tarix va sozlamalar.
| So'rovlar navbatda bajariladi (ProcessAiRequest), natija sahifada avtomatik yangilanadi.
*/
Route::middleware('permission:'.AdminSection::Ai->permission()->value)
    ->prefix('ai')
    ->name('ai.')
    ->group(function () {
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

        Route::middleware('permission:'.PermissionName::AiSettingsManage->value)->group(function () {
            Route::put('settings', [AiSettingsController::class, 'update'])->name('settings.update');
            Route::delete('settings/api-key', [AiSettingsController::class, 'destroyKey'])->name('settings.key.destroy');
            Route::put('prompts/{promptTemplate:key}', [AiSettingsController::class, 'updatePrompt'])->name('prompts.update');
            Route::post('prompts/{promptTemplate:key}/reset', [AiSettingsController::class, 'resetPrompt'])->name('prompts.reset');
            Route::put('limits/{user}', [AiSettingsController::class, 'updateLimit'])->name('limits.update');
        });
    });

/*
| Sozlamalar (kontent): ilmiy yo'nalishlar, maqola turlari va narxlar, bosh sahifa bannerlari
*/
Route::middleware('permission:'.AdminSection::Settings->permission()->value)
    ->prefix('settings')
    ->name('settings.')
    ->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');

        Route::post('subjects', [SubjectController::class, 'store'])->name('subjects.store');
        Route::put('subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
        Route::delete('subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');

        Route::middleware('permission:'.PermissionName::PricesManage->value)->group(function () {
            Route::post('types', [ArticleTypeController::class, 'store'])->name('types.store');
            Route::put('types/{articleType}', [ArticleTypeController::class, 'update'])->name('types.update');
            Route::delete('types/{articleType}', [ArticleTypeController::class, 'destroy'])->name('types.destroy');
        });

        Route::post('banners', [BannerController::class, 'store'])->name('banners.store');
        Route::put('banners/{banner}', [BannerController::class, 'update'])->name('banners.update');
        Route::delete('banners/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');

        Route::post('posts', [PostController::class, 'store'])->name('posts.store');
        Route::put('posts/{post}', [PostController::class, 'update'])->name('posts.update');
        Route::delete('posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

        Route::post('events', [EventController::class, 'store'])->name('events.store');
        Route::put('events/{event}', [EventController::class, 'update'])->name('events.update');
        Route::delete('events/{event}', [EventController::class, 'destroy'])->name('events.destroy');

        Route::post('books', [RecommendedBookController::class, 'store'])->name('books.store');
        Route::put('books/{book}', [RecommendedBookController::class, 'update'])->name('books.update');
        Route::delete('books/{book}', [RecommendedBookController::class, 'destroy'])->name('books.destroy');

        Route::post('partners', [PartnerController::class, 'store'])->name('partners.store');
        Route::put('partners/{partner}', [PartnerController::class, 'update'])->name('partners.update');
        Route::delete('partners/{partner}', [PartnerController::class, 'destroy'])->name('partners.destroy');
    });

/*
| Mualliflar va taqrizchilar
*/
Route::middleware('permission:'.AdminSection::Authors->permission()->value)
    ->prefix('authors')
    ->name('authors.')
    ->group(function () {
        Route::get('/', [AuthorController::class, 'index'])->name('index');
        Route::get('{user}', [AuthorController::class, 'show'])->name('show')->whereNumber('user');
    });

Route::middleware('permission:'.AdminSection::Reviewers->permission()->value)
    ->prefix('reviewers')
    ->name('reviewers.')
    ->group(function () {
        Route::get('/', [ReviewerController::class, 'index'])->name('index');
        Route::get('candidates', [ReviewerController::class, 'candidates'])->name('candidates');
        Route::post('/', [ReviewerController::class, 'store'])->name('store');
        Route::get('{user}', [ReviewerController::class, 'show'])->name('show')->whereNumber('user');
        Route::put('{user}/status', [ReviewerController::class, 'status'])->name('status');
        Route::put('{user}/subjects', [ReviewerController::class, 'subjects'])->name('subjects');
        Route::delete('{user}', [ReviewerController::class, 'destroy'])->name('destroy');
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
