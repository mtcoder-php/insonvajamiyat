<?php

namespace App\Http\Controllers\Cabinet;

use App\Enums\ArticleStatus;
use App\Enums\EditorialDecisionType;
use App\Enums\PaymentProvider;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\PermissionName;
use App\Enums\ReviewCriterion;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Cabinet\AuthorArticleResource;
use App\Models\AiRequest;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleFile;
use App\Models\ArticleStatusHistory;
use App\Models\Review;
use App\Models\User;
use App\Services\Ai\AiStudioPresenter;
use App\Services\Articles\ArticleTimeline;
use App\Services\Articles\ArticleWorkflow;
use App\Services\Articles\RevisionService;
use App\Services\Cabinet\AuthorDashboardService;
use App\Services\Messages\ArticleMessageService;
use App\Services\Payments\OnlinePaymentService;
use App\Services\Production\ProductionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Muallif kabineti — "Mening maqolalarim": ro'yxat, maqola sahifasi, qaytarib olish.
 * Yangi maqola yuborish — ArticleSubmissionController (7 bosqichli forma).
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

    public function show(
        Request $request,
        Article $article,
        ArticleTimeline $timeline,
        ArticleMessageService $messages,
        RevisionService $revisions,
        ProductionService $production,
        OnlinePaymentService $payments,
    ): Response {
        Gate::authorize('view', $article);

        /** @var User $user */
        $user = $request->user();
        $messages->markRead($article, $user);
        $revision = Gate::allows('update', $article) ? $revisions->request($article) : null;

        $article->load([
            'subject', 'articleType', 'issues', 'authors', 'files',
            'statusHistories' => fn ($q) => $q->where('is_visible_to_author', true),
        ]);

        $keywords = $article->getTranslation('keywords', app()->getLocale(), true);
        $isDraft = $article->status === ArticleStatus::Draft;

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
                    'edit' => $isDraft && Gate::allows('editDraft', $article),
                    'delete' => $isDraft && Gate::allows('delete', $article),
                ],
                'editUrl' => $isDraft ? route('cabinet.articles.edit', $article->uuid) : null,
                'destroyUrl' => $isDraft ? route('cabinet.articles.destroy', $article->uuid) : null,
            ],
            'steps' => $timeline->for($article),
            'payment' => $this->payment($article, $payments, $request->query('payment') === 'return'),
            'ai' => $user->can(PermissionName::AiUse->value) ? [
                'url' => route('cabinet.ai.index', ['article' => $article->uuid]),
                'requests' => AiRequest::query()
                    ->where('user_id', $user->id)
                    ->where('article_id', $article->id)
                    ->latest('id')
                    ->limit(5)
                    ->get()
                    ->map(fn (AiRequest $r): array => AiStudioPresenter::for('cabinet.ai')->item($r))
                    ->all(),
            ] : null,
            'reviews' => $this->reviews($article),
            'revision' => $revision !== null
                ? [...$revision, 'url' => route('cabinet.articles.revision.store', $article->uuid)]
                : null,
            'production' => $this->production($article, $user, $production),
            'messages' => [
                'items' => $messages->thread($article, $user),
                'sendUrl' => Gate::allows('message', $article)
                    ? route('cabinet.articles.messages.store', $article->uuid)
                    : null,
            ],
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
     * Nashrga tayyorlash bosqichi muallif uchun: korrektura (yakuniy PDF), DOI, jurnal soni.
     *
     * @return array<string, mixed>|null
     */
    private function production(Article $article, User $user, ProductionService $production): ?array
    {
        if (! in_array($article->status, [ArticleStatus::InProduction, ArticleStatus::Published], true)) {
            return null;
        }

        $finalPdf = $production->finalPdf($article);
        $checklist = $article->production_checklist ?? [];
        $placement = $article->placement()->with('issue')->first();
        $approved = $production->authorApproved($article, $finalPdf);
        $proof = $production->proofState($article, $finalPdf);
        $canRespond = $article->status === ArticleStatus::InProduction
            && $finalPdf !== null
            && $article->submitter_id === $user->id;

        return [
            'proof' => $finalPdf !== null ? [
                'name' => $finalPdf->original_name,
                'size' => $finalPdf->size,
                'uploadedAt' => $finalPdf->created_at?->toIso8601String(),
                'viewUrl' => route('cabinet.articles.files.download', [$article->uuid, $finalPdf->uuid, 'inline' => 1]),
                'downloadUrl' => route('cabinet.articles.files.download', [$article->uuid, $finalPdf->uuid]),
            ] : null,
            'approved' => $approved,
            'state' => $proof['state'],
            'dueAt' => $proof['dueAt'],
            'waivedAt' => $proof['waived']['at'] ?? null,
            'deadlineDays' => ProductionService::proofDeadlineDays(),
            'approvedAt' => $approved && is_string($checklist['author_approved_at'] ?? null) ? $checklist['author_approved_at'] : null,
            'changes' => ! $approved && is_string($checklist['author_changes'] ?? null) ? $checklist['author_changes'] : null,
            'readyForPublication' => $article->chief_editor_approved_at !== null,
            'publishedAt' => $article->published_at?->toIso8601String(),
            'publicUrl' => $article->isPublished() ? route('articles.show', (string) $article->slug) : null,
            'issue' => $placement?->issue->label,
            'pages' => $placement?->pages(),
            'doi' => $article->doi,
            'canRespond' => $canRespond,
            'approveUrl' => route('cabinet.articles.proof.approve', $article->uuid),
            'changesUrl' => route('cabinet.articles.proof.changes', $article->uuid),
        ];
    }

    /**
     * Taqriz natijalari muallif uchun (blind review): taqrizchi ismi o'rniga "Taqrizchi N",
     * faqat yakunlangan va shu raund bo'yicha muharrir qarori chiqqan taqrizlar.
     *
     * @return array<int, array<string, mixed>>
     */
    private function reviews(Article $article): array
    {
        $decidedRounds = $article->decisions()
            ->where('decision', '!=', EditorialDecisionType::SendToReview->value)
            ->pluck('round')
            ->all();

        return $article->reviews()
            ->where('status', ReviewStatus::Completed->value)
            ->whereIn('round', $decidedRounds)
            ->get()
            ->groupBy('round')
            ->sortKeysDesc()
            ->flatMap(fn ($reviews) => $reviews->values()->map(fn (Review $review, int $i): array => [
                'id' => $review->id,
                'label' => __('Taqrizchi :n', ['n' => $i + 1]),
                'round' => $review->round,
                'recommendation' => $review->recommendation?->label(),
                'score' => $review->score !== null ? (float) $review->score : null,
                'criteria' => array_map(fn (ReviewCriterion $c): array => [
                    'label' => $c->label(),
                    'value' => isset($review->criteria_scores[$c->value]) ? (float) $review->criteria_scores[$c->value] : null,
                ], ReviewCriterion::cases()),
                'comments' => $review->comments_to_author,
                'completedAt' => $review->completed_at?->toIso8601String(),
            ]))
            ->values()
            ->all();
    }

    /**
     * Nashr to'lovi: summa, holat, chek raqami (qaytarilgan bo'lsa — sanasi); "To'lov kutilmoqda" da — bank rekvizitlari.
     * Qoralama va bepul (ozod qilingan) maqolada to'lov bloki ko'rsatilmaydi.
     *
     * @return array<string, mixed>|null
     */
    private function payment(Article $article, OnlinePaymentService $payments, bool $returned): ?array
    {
        $payment = $article->payments()
            ->where('purpose', PaymentPurpose::Publication->value)
            ->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::Refunded->value])
            ->latest('paid_at')
            ->first();
        $awaiting = $article->status === ArticleStatus::AwaitingPayment;

        if (! $awaiting && $payment === null) {
            return null;
        }

        $requisites = array_filter(
            (array) config('journal.payment', []),
            fn (mixed $value): bool => is_string($value) && $value !== '',
        );

        return [
            'awaiting' => $awaiting,
            'amount' => $payment !== null ? (float) $payment->amount : (float) $article->articleType->price,
            'currency' => $article->articleType->currency,
            'status' => $article->payment_status->value,
            'statusLabel' => $article->payment_status->label(),
            'receipt' => $payment?->receipt_number,
            'provider' => $payment?->provider->label(),
            'paidAt' => $payment?->paid_at?->toIso8601String(),
            'refundedAt' => $payment?->refunded_at?->toIso8601String(),
            'requisites' => $awaiting ? $requisites : [],
            'purpose' => $awaiting
                ? __("Nashr to'lovi: maqola :id", ['id' => mb_strtoupper(mb_substr($article->uuid, 0, 8))])
                : null,
            'online' => $awaiting && Gate::allows('pay', $article) ? $this->onlineOptions($article, $payments, $returned) : null,
        ];
    }

    /**
     * Click / Payme tugmalari va oxirgi onlayn urinish holati.
     *
     * @return array<string, mixed>
     */
    private function onlineOptions(Article $article, OnlinePaymentService $payments, bool $returned): array
    {
        $attempt = $payments->lastAttempt($article);

        return [
            'payUrl' => route('cabinet.articles.pay', $article->uuid),
            'providers' => array_map(fn (PaymentProvider $provider): array => [
                'value' => $provider->value,
                'label' => $provider->label(),
            ], $payments->providers()),
            'processing' => $attempt !== null && $attempt->status === PaymentStatus::Processing,
            'lastAttempt' => $attempt !== null && $attempt->status !== PaymentStatus::Pending ? [
                'provider' => $attempt->provider->label(),
                'status' => $attempt->status->value,
                'statusLabel' => $attempt->status->label(),
                'at' => $attempt->updated_at?->toIso8601String(),
            ] : null,
            'returned' => $returned,
            'pollSeconds' => (int) config('payments.return_poll_seconds', 120),
        ];
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
