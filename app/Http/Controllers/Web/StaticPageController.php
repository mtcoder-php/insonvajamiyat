<?php

namespace App\Http\Controllers\Web;

use App\Enums\PageSlug;
use App\Enums\PartnerType;
use App\Http\Controllers\Controller;
use App\Models\ArticleType;
use App\Models\Partner;
use App\Models\Subject;
use App\Services\Content\EditorialBoardService;
use App\Services\Content\PageService;
use App\Services\Web\CatalogService;
use App\Support\MediaUrl;
use App\Support\Seo\SeoMeta;
use App\Support\Translations;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Jurnal haqida", "Mualliflar uchun yo'riqnoma", "Aloqa": tahririyat yozgan bo'limlar
 * (PageService) + ma'lumotlar bazasidan avtomatik bloklar.
 */
class StaticPageController extends Controller
{
    public function __construct(private readonly PageService $pages) {}

    public function about(EditorialBoardService $board, CatalogService $catalog): Response
    {
        $page = $this->page(PageSlug::About);

        return Inertia::render('web/About', [
            'page' => $page,
            'board' => $board->public(),
            'facts' => $this->facts(),
            'subjects' => Subject::query()->active()->get()
                ->map(fn (Subject $s): array => ['name' => $s->name, 'slug' => $s->slug])
                ->values()->all(),
            'indexing' => Partner::query()->active()->where('type', PartnerType::Indexing->value)->get()
                ->map(fn (Partner $p): array => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'url' => $p->url,
                    'logoUrl' => MediaUrl::from($p->logo_path),
                ])->values()->all(),
            'stats' => $catalog->stats(),
        ]);
    }

    public function guidelines(Request $request): Response
    {
        $page = $this->page(PageSlug::Guidelines);

        return Inertia::render('web/Guidelines', [
            'page' => $page,
            'template' => MediaUrl::publicAsset(config('journal.article_template')),
            'types' => ArticleType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
                ->map(fn (ArticleType $type): array => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'description' => $type->description,
                    'price' => (float) $type->price,
                    'currency' => $type->currency,
                    'reviewDays' => $type->review_days,
                ])->values()->all(),
            'plagiarismMax' => (float) config('journal.plagiarism_max', 20),
            // Kirgan muallif — to'g'ridan-to'g'ri yuborish formasi, mehmon — ro'yxatdan o'tish
            'submitUrl' => $request->user() !== null ? route('cabinet.articles.create') : route('register'),
        ]);
    }

    public function contact(): Response
    {
        return Inertia::render('web/Contact', [
            'page' => $this->page(PageSlug::Contact),
        ]);
    }

    /**
     * @return array{title: string, description: string, sections: list<array{heading: string, body: string}>, updatedAt: string|null}
     */
    private function page(PageSlug $slug): array
    {
        $page = $this->pages->public($slug);

        if ($page['description'] !== '') {
            app(SeoMeta::class)->set($page['title'], $page['description']);
        }

        return $page;
    }

    /**
     * Jurnal rekvizitlari (Tizim sozlamalari → Jurnal).
     *
     * @return list<array{label: string, value: string}>
     */
    private function facts(): array
    {
        $facts = [];
        $add = function (string $label, mixed $value) use (&$facts): void {
            if (is_scalar($value) && trim((string) $value) !== '') {
                $facts[] = ['label' => $label, 'value' => trim((string) $value)];
            }
        };

        $add(__('ISSN (bosma)'), config('journal.issn'));
        $add(__('e-ISSN (elektron)'), config('journal.eissn'));
        $add(__('DOI prefiksi'), config('journal.doi_prefix'));
        $add(__('Davriylik'), Translations::line(config('journal.frequency')));
        $add(__('Nashr tillari'), __("O'zbek, rus, ingliz"));
        $add(__('Kirish'), __('Ochiq kirish (Open Access)'));
        $add(__('Taqriz'), __('Yashirin taqriz (blind review)'));

        return $facts;
    }
}
