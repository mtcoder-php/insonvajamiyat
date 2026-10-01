<?php

namespace App\Services\Editorial;

use App\Enums\ArticleStatus;
use App\Enums\EditorialDecisionType;
use App\Enums\Language;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleFile;
use App\Models\ArticleNote;
use App\Models\ArticleStatusHistory;
use App\Models\ArticleVersion;
use App\Models\EditorialDecision;
use App\Models\User;
use App\Services\Articles\ArticleTimeline;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * Muharrir ish joyi (admin muharir.png) ma'lumotlari: navbatlar (tablar), statistika,
 * maqolalar ro'yxati va tanlangan maqola kartasi.
 */
class EditorialWorkspace
{
    public const PER_PAGE = 12;

    /** Navbat → holatlar ("mine" va "all" — maxsus) */
    public const QUEUES = [
        'new' => [ArticleStatus::Submitted],
        'reviewing' => [ArticleStatus::UnderReview, ArticleStatus::InReview, ArticleStatus::Resubmitted],
        'revision' => [ArticleStatus::RevisionRequired],
        'accepted' => [ArticleStatus::Accepted, ArticleStatus::InProduction],
        'payment' => [ArticleStatus::AwaitingPayment],
        'published' => [ArticleStatus::Published],
        'closed' => [ArticleStatus::Rejected, ArticleStatus::Withdrawn],
        'mine' => [],
        'all' => [],
    ];

    public function __construct(private readonly ArticleTimeline $timeline) {}

    /**
     * @return array<string, int>
     */
    public function counts(User $user): array
    {
        $byStatus = $this->base()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $counts = [];

        foreach (self::QUEUES as $key => $statuses) {
            $counts[$key] = (int) collect($statuses)->sum(fn (ArticleStatus $s): int => (int) ($byStatus[$s->value] ?? 0));
        }

        $counts['all'] = (int) $byStatus->sum();
        $counts['mine'] = $this->queueQuery('mine', $user)->count();

        return $counts;
    }

    /**
     * Statistika kartalari: navbatdagi soni va shu oyda qo'shilganlari.
     *
     * @return array<int, array{key: string, value: int, month: int}>
     */
    public function stats(User $user): array
    {
        $counts = $this->counts($user);
        $since = now()->startOfMonth();

        $month = fn (?array $statuses, string $column): int => $this->base()
            ->when($statuses !== null, fn (Builder $q) => $q->whereIn('status', $this->values($statuses ?? [])))
            ->where($column, '>=', $since)
            ->count();

        return [
            ['key' => 'all', 'value' => $counts['all'], 'month' => $month(null, 'submitted_at')],
            ['key' => 'new', 'value' => $counts['new'], 'month' => $month(self::QUEUES['new'], 'submitted_at')],
            ['key' => 'reviewing', 'value' => $counts['reviewing'], 'month' => $month(self::QUEUES['reviewing'], 'updated_at')],
            ['key' => 'revision', 'value' => $counts['revision'], 'month' => $month(self::QUEUES['revision'], 'updated_at')],
            ['key' => 'accepted', 'value' => $counts['accepted'], 'month' => $month(self::QUEUES['accepted'], 'accepted_at')],
        ];
    }

    /**
     * @return LengthAwarePaginator<int, Article>
     */
    public function list(string $queue, ?string $search, User $user): LengthAwarePaginator
    {
        $query = $this->queueQuery($queue, $user)->with(['submitter', 'subject', 'handlingEditor']);

        if ($search !== null) {
            $like = '%'.$search.'%';
            $query->where(fn (Builder $q) => $q
                ->where('title->uz', 'like', $like)
                ->orWhere('title->ru', 'like', $like)
                ->orWhere('title->en', 'like', $like)
                ->orWhereHas('authors', fn (Builder $a) => $a
                    ->where('last_name', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('email', 'like', $like)));
        }

        // Yangi navbat — eng uzoq kutayotgan birinchi; qolganlari — oxirgi o'zgargan birinchi
        if ($queue === 'new') {
            $query->oldest('submitted_at')->oldest('id');
        } else {
            $query->latest('updated_at')->latest('id');
        }

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function listItem(Article $article): array
    {
        return [
            'uuid' => $article->uuid,
            'code' => self::code($article),
            'title' => $article->title,
            'author' => $article->submitter->name,
            'subject' => $article->subject?->name,
            'status' => $article->status->value,
            'statusGroup' => $article->status->group(),
            'statusLabel' => $article->status->label(),
            'editor' => $article->handlingEditor?->name,
            'date' => ($article->submitted_at ?? $article->created_at)?->toIso8601String(),
            'updatedAt' => $article->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Tanlangan maqola kartasi (o'rta va o'ng ustunlar).
     *
     * @return array<string, mixed>
     */
    public function detail(Article $article, User $user): array
    {
        $article->load([
            'submitter.authorProfile', 'subject', 'articleType', 'issues', 'authors', 'handlingEditor',
            'files', 'versions.files', 'statusHistories.changedBy', 'notes.user', 'decisions.editor',
        ]);

        $canDecide = Gate::forUser($user)->allows('decide', $article);

        return [
            'uuid' => $article->uuid,
            'code' => self::code($article),
            'title' => $article->title,
            'status' => $article->status->value,
            'statusGroup' => $article->status->group(),
            'statusLabel' => $article->status->label(),
            'type' => $article->articleType->name,
            'subject' => $article->subject?->name,
            'language' => $article->language,
            'udc' => $article->udc,
            'issue' => $article->issues->first()?->label,
            'submittedAt' => $article->submitted_at?->toIso8601String(),
            'paymentStatusLabel' => $article->payment_status->label(),
            'reviewRound' => $article->review_round,
            'submitter' => [
                'name' => $article->submitter->name,
                'email' => $article->submitter->email,
                'organization' => $article->submitter->authorProfile?->organization,
            ],
            'handlingEditor' => $article->handlingEditor !== null
                ? ['id' => $article->handlingEditor->id, 'name' => $article->handlingEditor->name]
                : null,
            'abstracts' => $this->localized($article, 'abstract'),
            'titles' => $this->localized($article, 'title'),
            'keywords' => $this->keywords($article),
            'references' => $article->references,
            'authors' => $article->authors->map(fn (ArticleAuthor $author): array => [
                'name' => $author->full_name,
                'organization' => $author->organization,
                'email' => $author->email,
                'orcid' => $author->orcid,
                'degree' => $author->academic_degree,
                'isCorresponding' => $author->is_corresponding,
            ])->all(),
            'files' => $article->files->map(fn (ArticleFile $file): array => $this->file($article, $file))->all(),
            'versions' => $article->versions->sortByDesc('version_number')->values()->map(fn (ArticleVersion $version): array => [
                'number' => $version->version_number,
                'type' => $version->type->label(),
                'round' => $version->review_round,
                'note' => $version->change_note,
                'createdAt' => $version->created_at?->toIso8601String(),
                'files' => $version->files->map(fn (ArticleFile $file): array => $this->file($article, $file))->all(),
            ])->all(),
            'history' => $article->statusHistories->reverse()->values()->map(fn (ArticleStatusHistory $h): array => [
                'id' => $h->id,
                'statusGroup' => $h->to_status->group(),
                'statusLabel' => $h->to_status->label(),
                'comment' => $h->comment,
                'visibleToAuthor' => $h->is_visible_to_author,
                'actor' => $h->changedBy?->name,
                'createdAt' => $h->created_at->toIso8601String(),
            ])->all(),
            'decisions' => $article->decisions->map(fn (EditorialDecision $d): array => [
                'id' => $d->id,
                'decision' => $d->decision->value,
                'label' => $d->decision->label(),
                'round' => $d->round,
                'commentToAuthor' => $d->comment_to_author,
                'internalNote' => $d->internal_note,
                'editor' => $d->editor->name,
                'createdAt' => $d->created_at->toIso8601String(),
            ])->all(),
            'notes' => $article->notes->map(fn (ArticleNote $note): array => [
                'id' => $note->id,
                'body' => $note->body,
                'author' => $note->user->name,
                'avatar' => $note->user->avatarUrl(),
                'role' => $this->roleOf($note->user),
                'mine' => $note->user_id === $user->id,
                'createdAt' => $note->created_at->toIso8601String(),
            ])->all(),
            'steps' => $this->timeline->for($article),
            'can' => [
                'startReview' => $canDecide && in_array($article->status, [ArticleStatus::Submitted, ArticleStatus::Resubmitted], true),
                'decide' => $canDecide,
                'assign' => $canDecide && ! $article->status->isFinal(),
                'note' => true,
            ],
            'availableDecisions' => $canDecide
                ? array_values(array_map(
                    fn (EditorialDecisionType $type): string => $type->value,
                    array_filter(
                        EditorialService::DECISIONS,
                        fn (EditorialDecisionType $type): bool => $article->status->canTransitionTo($type->resultingStatus()),
                    ),
                ))
                : [],
            'urls' => [
                'startReview' => route('admin.articles.start-review', $article->uuid),
                'decision' => route('admin.articles.decision', $article->uuid),
                'editor' => route('admin.articles.editor', $article->uuid),
                'notes' => route('admin.articles.notes', $article->uuid),
            ],
        ];
    }

    /**
     * Mas'ul muharrir bo'la oladiganlar (blokdagi emas).
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function editors(): array
    {
        return User::query()
            ->active()
            ->role([RoleName::SuperAdmin->value, RoleName::ChiefEditor->value, RoleName::Editor->value])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->filter(fn (User $user): bool => $user->can(PermissionName::ArticlesDecide->value))
            ->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name])
            ->values()
            ->all();
    }

    /** "2026-0048" — maqolaning tahririyat raqami */
    public static function code(Article $article): string
    {
        $year = ($article->submitted_at ?? $article->created_at)?->year ?? (int) now()->year;

        return sprintf('%d-%04d', $year, $article->id);
    }

    /**
     * @return Builder<Article>
     */
    public function queueQuery(string $queue, User $user): Builder
    {
        $query = $this->base();

        return match ($queue) {
            'all' => $query,
            'mine' => $query
                ->where('handling_editor_id', $user->id)
                ->whereNotIn('status', $this->values([ArticleStatus::Published, ArticleStatus::Rejected, ArticleStatus::Withdrawn])),
            default => $query->whereIn('status', $this->values(self::QUEUES[$queue] ?? [])),
        };
    }

    /**
     * Qoralamalar tahririyatga ko'rinmaydi.
     *
     * @return Builder<Article>
     */
    private function base(): Builder
    {
        return Article::query()->where('status', '!=', ArticleStatus::Draft->value);
    }

    /**
     * @return array<string, mixed>
     */
    private function file(Article $article, ArticleFile $file): array
    {
        return [
            'uuid' => $file->uuid,
            'name' => $file->original_name,
            'typeLabel' => $file->type->label(),
            'extension' => $file->extension(),
            'size' => $file->size,
            'uploadedAt' => $file->created_at?->toIso8601String(),
            'url' => route('cabinet.articles.files.download', [$article->uuid, $file->uuid]),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function localized(Article $article, string $key): array
    {
        $values = [];

        foreach (Language::cases() as $language) {
            $value = $article->getTranslation($key, $language->value, false);
            $values[$language->value] = is_string($value) && $value !== '' ? $value : null;
        }

        return $values;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function keywords(Article $article): array
    {
        $values = [];

        foreach (Language::cases() as $language) {
            $words = $article->getTranslation('keywords', $language->value, false);
            $values[$language->value] = is_array($words) ? array_values(array_filter($words, 'is_string')) : [];
        }

        return $values;
    }

    private function roleOf(User $user): ?string
    {
        $role = RoleName::tryFrom((string) $user->getRoleNames()->first());

        return $role?->label();
    }

    /**
     * @param  array<int, ArticleStatus>  $statuses
     * @return array<int, string>
     */
    private function values(array $statuses): array
    {
        return array_map(fn (ArticleStatus $status): string => $status->value, $statuses);
    }
}
