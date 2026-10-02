<?php

namespace App\Http\Controllers\Admin\Production;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Production\ProductionMetadataRequest;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Models\User;
use App\Services\Articles\ArticleCoverService;
use App\Services\Articles\ArticleFileService;
use App\Services\Editorial\EditorialService;
use App\Services\Production\ProductionService;
use App\Services\Production\ProductionWorkspace;
use App\Services\Publishing\PublishService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Nashr jarayoni (admin publisher page.png): maketlash, yakuniy PDF, nashr oldidan
 * tekshiruv va bosh muharrir tasdig'i.
 */
class ProductionController extends Controller
{
    /** Nashr bo'limida ko'rinadigan holatlar */
    private const VISIBLE = [ArticleStatus::Accepted, ArticleStatus::InProduction, ArticleStatus::Published];

    public function __construct(
        private readonly ProductionService $production,
        private readonly ProductionWorkspace $workspace,
    ) {}

    public function index(Request $request): Response
    {
        $counts = $this->workspace->counts();
        $requested = $request->string('tab')->toString();
        $tab = in_array($requested, ProductionWorkspace::TABS, true)
            ? $requested
            : ($counts['new'] > 0 ? 'new' : ($counts['production'] > 0 ? 'production' : 'all'));
        $search = $request->string('search')->trim()->limit(100, '')->toString();
        $articles = $this->workspace->list($tab, $search !== '' ? $search : null);

        return Inertia::render('admin/production/Index', [
            'filters' => ['tab' => $tab, 'search' => $search !== '' ? $search : null],
            'counts' => $counts,
            'stats' => $this->workspace->stats(),
            'articles' => [
                'data' => $articles->getCollection()->map(fn (Article $a): array => $this->workspace->listItem($a))->all(),
                'meta' => [
                    'current_page' => $articles->currentPage(),
                    'last_page' => $articles->lastPage(),
                    'per_page' => $articles->perPage(),
                    'total' => $articles->total(),
                    'from' => $articles->firstItem(),
                    'to' => $articles->lastItem(),
                    'links' => $articles->linkCollection()->all(),
                ],
            ],
        ]);
    }

    public function show(Request $request, Article $article): Response
    {
        $this->ensureVisible($article);

        return Inertia::render('admin/production/Show', [
            'article' => $this->workspace->detail($article, $this->user($request)),
            'issues' => fn () => $this->workspace->issues(),
        ]);
    }

    public function start(Request $request, Article $article): RedirectResponse
    {
        $this->production->start($article, $this->user($request));

        return $this->done(__('Maqola maketga olindi.'));
    }

    /** Maqola rasmi (saytdagi katalog va maqola sahifasi uchun) — nashrdan keyin ham almashtiriladi */
    public function uploadCover(Request $request, Article $article, ArticleCoverService $covers): RedirectResponse
    {
        $request->validate(
            ['cover' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=600,min_height=400']],
            [],
            ['cover' => __('Maqola rasmi')],
        );
        $image = $request->file('cover');
        abort_unless($image instanceof UploadedFile, 422);

        $covers->store($article, $image);

        return $this->done(__('Maqola rasmi yangilandi.'));
    }

    public function removeCover(Article $article, ArticleCoverService $covers): RedirectResponse
    {
        $covers->remove($article);

        return $this->done(__("Maqola rasmi o'chirildi."));
    }

    public function uploadFinalPdf(Request $request, Article $article): RedirectResponse
    {
        $request->validate(
            ['pdf' => ['required', 'file', 'mimes:pdf', 'max:30720']],
            [],
            ['pdf' => __('Yakuniy PDF')],
        );
        $pdf = $request->file('pdf');
        abort_unless($pdf instanceof UploadedFile, 422);

        $this->production->uploadFinalPdf($article, $pdf, $this->user($request));

        return $this->done(__('Yakuniy PDF yuklandi. Muallifga korrekturani tekshirish uchun xabar yuborildi.'));
    }

    public function metadata(ProductionMetadataRequest $request, Article $article): RedirectResponse
    {
        $this->production->updateMetadata($article, $request->metadata());

        return $this->done(__("Nashr ma'lumotlari saqlandi."));
    }

    public function format(Request $request, Article $article): RedirectResponse
    {
        $request->validate(['ok' => ['required', 'boolean']]);
        $this->production->setFormat($article, $request->boolean('ok'));

        return $this->done(__('Tekshiruv yangilandi.'));
    }

    /** Korrektura muddati o'tdi, muallif javob bermadi — bosh muharrir qarori bilan davom ettirish */
    public function waiveProof(Request $request, Article $article): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);

        $this->production->waiveAuthorApproval($article, $this->user($request), (string) $validated['reason']);

        return $this->done(__('Korrektura tahririyat qarori bilan tasdiqlandi.'));
    }

    public function approve(Request $request, Article $article): RedirectResponse
    {
        $this->production->approve($article, $this->user($request));

        return $this->done(__('Maqola nashrga tasdiqlandi.'));
    }

    /** Chop etilgan songa keyin qo'shilgan maqolani alohida chop etish */
    public function publish(Request $request, Article $article, PublishService $publisher): RedirectResponse
    {
        $publisher->publishArticle($article, $this->user($request));

        return $this->done(__('Maqola chop etildi va saytda e\'lon qilindi.'));
    }

    public function revoke(Article $article): RedirectResponse
    {
        $this->production->revokeApproval($article);

        return $this->done(__('Tasdiq bekor qilindi.'));
    }

    public function cancel(Request $request, Article $article): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $reason = is_string($validated['reason'] ?? null) ? $validated['reason'] : null;

        $this->production->cancel($article, $this->user($request), $reason);

        return $this->done(__('Maqola maketdan qaytarildi.'));
    }

    public function addNote(Request $request, Article $article, EditorialService $editorial): RedirectResponse
    {
        $this->ensureVisible($article);
        $validated = $request->validate(['body' => ['required', 'string', 'max:2000']], [], ['body' => __('Izoh')]);

        $editorial->addNote($article, $this->user($request), (string) $validated['body']);

        return $this->done(__("Izoh qo'shildi."));
    }

    /** Fayl: PDF — brauzerda ko'rish (iframe), ?download=1 — yuklab olish */
    public function file(Request $request, Article $article, ArticleFile $file, ArticleFileService $files): StreamedResponse
    {
        $this->ensureVisible($article);

        return $request->boolean('download') ? $files->download($file) : $files->inline($file);
    }

    private function ensureVisible(Article $article): void
    {
        abort_unless(in_array($article->status, self::VISIBLE, true), 404);
    }

    private function done(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
