<?php

namespace App\Http\Controllers\Cabinet;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Cabinet\AuthorArticleResource;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleFile;
use App\Models\ArticleStatusHistory;
use App\Models\User;
use App\Services\Articles\ArticleTimeline;
use App\Services\Articles\ArticleWorkflow;
use App\Services\Cabinet\AuthorDashboardService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Muallif kabineti — "Mening maqolalarim": ro'yxat, maqola sahifasi, qaytarib olish.
 * Yangi maqola yuborish (create/store) — keyingi bosqichda (bosqichma-bosqich forma).
 */
class ArticleController extends Controller
{
    public const PER_PAGE = 10;

    /** Filtr tablari: kalit => holatlar */
    public const FILTERS = [
        'reviewing' => AuthorDashboardService::REVIEWING,
        'revision' => [ArticleStatus::RevisionRequired],
        'accepted' => [ArticleStatus::Accepted, ArticleStatus::InProduction],
        'published' => [ArticleStatus::Published],
        'draft' => [ArticleStatus::Draft],
        'closed' => [ArticleStatus::Rejected, ArticleStatus::Withdrawn],
    ];

    public function __construct(private readonly AuthorDashboardService $dashboard) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $filter = array_key_exists((string) $request->query('status'), self::FILTERS)
            ? $request->string('status')->toString()
            : null;
        $search = $request->string('search')->trim()->limit(100, '')->toString();

        $query = $this->dashboard->articles($user)->with(['subject', 'issues']);

        if ($filter !== null) {
            $query->whereIn('status', array_map(fn (ArticleStatus $s): string => $s->value, self::FILTERS[$filter]));
        }

        if ($search !== '') {
            // Sarlavha tarjimali (JSON) — joriy til va o'zbekcha qiymat bo'yicha qidiriladi
            $like = '%'.$search.'%';
            $query->where(fn (Builder $q) => $q
                ->where('title->'.app()->getLocale(), 'like', $like)
                ->orWhere('title->uz', 'like', $like));
        }

        $articles = $query->latest('updated_at')->latest('id')->paginate(self::PER_PAGE)->withQueryString();

        return Inertia::render('cabinet/articles/Index', [
            'articles' => AuthorArticleResource::collection($articles),
            'filters' => ['status' => $filter, 'search' => $search !== '' ? $search : null],
            'counts' => fn () => $this->counts($user),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('cabinet/articles/Create', [
            'links' => DashboardController::links(),
        ]);
    }

    public function show(Request $request, Article $article, ArticleTimeline $timeline): Response
    {
        Gate::authorize('view', $article);

        $article->load([
            'subject', 'articleType', 'issues', 'authors', 'files',
            'statusHistories' => fn ($q) => $q->where('is_visible_to_author', true),
        ]);

        $keywords = $article->getTranslation('keywords', app()->getLocale(), true);

        return Inertia::render('cabinet/articles/Show', [
            'article' => [
                ...AuthorArticleResource::make($article)->resolve(),
                'abstract' => $article->abstract,
                'keywords' => is_array($keywords) ? array_values(array_filter($keywords, 'is_string')) : [],
                'type' => $article->articleType->name,
                'language' => $article->language,
                'udc' => $article->udc,
                'doi' => $article->doi,
                'paymentStatus' => $article->payment_status->value,
                'paymentStatusLabel' => $article->payment_status->label(),
                'reviewRound' => $article->review_round,
                'acceptedAt' => $article->accepted_at?->toIso8601String(),
                'publishedAt' => $article->published_at?->toIso8601String(),
                'authors' => $article->authors->map(fn (ArticleAuthor $author): array => [
                    'id' => $author->id,
                    'name' => $author->full_name,
                    'organization' => $author->organization,
                    'email' => $author->email,
                    'orcid' => $author->orcid,
                    'isCorresponding' => $author->is_corresponding,
                ])->all(),
                'files' => $article->files->map(fn (ArticleFile $file): array => [
                    'id' => $file->id,
                    'name' => $file->original_name,
                    'type' => $file->type->value,
                    'typeLabel' => $file->type->label(),
                    'extension' => $file->extension(),
                    'size' => $file->size,
                    'uploadedAt' => $file->created_at?->toIso8601String(),
                    'url' => route('cabinet.articles.files.download', [$article->uuid, $file->uuid]),
                ])->all(),
                'history' => $article->statusHistories->reverse()->values()->map(fn (ArticleStatusHistory $h): array => [
                    'id' => $h->id,
                    'status' => $h->to_status->value,
                    'statusGroup' => $h->to_status->group(),
                    'statusLabel' => $h->to_status->label(),
                    'comment' => $h->comment,
                    'createdAt' => $h->created_at->toIso8601String(),
                ])->all(),
                'can' => [
                    'withdraw' => Gate::allows('withdraw', $article),
                    'update' => Gate::allows('update', $article),
                ],
            ],
            'steps' => $timeline->for($article),
        ]);
    }

    public function withdraw(Request $request, Article $article, ArticleWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('withdraw', $article);

        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $reason = is_string($validated['reason'] ?? null) ? $validated['reason'] : null;

        /** @var User $user */
        $user = $request->user();

        $workflow->transition($article, ArticleStatus::Withdrawn, $user, $reason);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Maqola qaytarib olindi.')]);

        return to_route('cabinet.articles.show', $article->uuid);
    }

    /**
     * Filtr tablari uchun sonlar.
     *
     * @return array<string, int>
     */
    private function counts(User $user): array
    {
        $byStatus = $this->dashboard->articles($user)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $counts = ['all' => (int) $byStatus->sum()];

        foreach (self::FILTERS as $key => $statuses) {
            $counts[$key] = (int) collect($statuses)->sum(fn (ArticleStatus $s): int => (int) ($byStatus[$s->value] ?? 0));
        }

        return $counts;
    }
}
