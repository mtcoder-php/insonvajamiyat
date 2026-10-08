<?php

namespace App\Http\Controllers\Web;

use App\Enums\IssueStatus;
use App\Http\Controllers\Controller;
use App\Models\JournalIssue;
use App\Services\Web\CatalogService;
use App\Services\Web\IssueArchiveService;
use App\Support\MediaUrl;
use App\Support\Seo\SeoMeta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Jurnal sonlari arxivi (web jurnal sonlari.png) va son sahifasi.
 */
class IssueController extends Controller
{
    public function index(Request $request, IssueArchiveService $archive, CatalogService $catalog): Response
    {
        $tree = $archive->tree();
        $years = array_column($tree, 'year');
        $year = $request->filled('year') && in_array($request->integer('year'), $years, true)
            ? $request->integer('year')
            : ($years[0] ?? (int) now()->year);
        $sort = $request->string('sort')->toString() === 'oldest' ? 'oldest' : 'newest';
        app(SeoMeta::class)->describe('«:name» ilmiy jurnalining barcha sonlari: maqolalar ro\'yxati va elektron versiyalar.');

        return Inertia::render('web/issues/Index', [
            'filters' => ['year' => $year, 'sort' => $sort],
            'tree' => $tree,
            'latest' => $archive->latest(),
            'yearIssues' => $archive->year($year, $sort),
            'subjects' => fn () => $catalog->subjects(),
            'latestArticles' => fn () => $archive->latestArticles(),
            'hero' => MediaUrl::publicAsset(config('journal.heroes.issues')),
        ]);
    }

    public function show(JournalIssue $issue, IssueArchiveService $archive): Response
    {
        abort_unless($issue->status === IssueStatus::Published, 404);
        app(SeoMeta::class)->forIssue($issue);

        return Inertia::render('web/issues/Show', $archive->show($issue));
    }
}
