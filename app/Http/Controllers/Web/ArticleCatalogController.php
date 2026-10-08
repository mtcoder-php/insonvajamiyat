<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\Web\CatalogService;
use App\Support\MediaUrl;
use App\Support\Seo\SeoMeta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Maqolalar katalogi (web maqola katalogi.png).
 */
class ArticleCatalogController extends Controller
{
    public function __invoke(Request $request, CatalogService $catalog): Response
    {
        $filters = $this->filters($request);
        $articles = $catalog->search($filters);
        app(SeoMeta::class)->describe('«:name» jurnalida nashr etilgan ilmiy maqolalar: mavzu, muallif, yil va kalit so\'z bo\'yicha qidiruv.');

        return Inertia::render('web/articles/Index', [
            'filters' => $filters,
            'articles' => [
                'data' => $articles->getCollection()->map(fn (Article $a): array => $catalog->card($a))->all(),
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
            'facets' => fn () => [
                'subjects' => $catalog->subjects(),
                'years' => $catalog->years(),
                'issues' => $catalog->issues(),
            ],
            'sidebar' => fn () => [
                'keywords' => $catalog->popularKeywords(),
                'stats' => $catalog->stats(),
                'latestIssue' => $catalog->latestIssue(),
                'announcements' => $catalog->announcements(),
            ],
            'hero' => MediaUrl::publicAsset(config('journal.heroes.catalog')),
        ]);
    }

    /**
     * @return array{q: string|null, subjects: array<int, string>, year: int|null, issue: string|null, author: string|null, keyword: string|null, sort: string}
     */
    private function filters(Request $request): array
    {
        $text = fn (string $key): ?string => ($value = $request->string($key)->trim()->limit(100, '')->toString()) !== '' ? $value : null;
        $subjects = $request->input('subjects', []);
        $sort = $request->string('sort')->toString();

        return [
            'q' => $text('q'),
            'subjects' => is_array($subjects) ? array_values(array_filter(array_slice($subjects, 0, 20), 'is_string')) : [],
            'year' => $request->filled('year') ? $request->integer('year') : null,
            'issue' => $text('issue'),
            'author' => $text('author'),
            'keyword' => $text('keyword'),
            'sort' => in_array($sort, CatalogService::SORTS, true) ? $sort : 'newest',
        ];
    }
}
