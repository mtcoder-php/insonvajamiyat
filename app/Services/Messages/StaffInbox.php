<?php

namespace App\Services\Messages;

use App\Enums\ArticleStatus;
use App\Enums\MessageChannel;
use App\Models\Article;
use App\Models\Message;
use App\Models\User;
use App\Services\Editorial\EditorialWorkspace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;

/**
 * Admin → Xabarlar: barcha maqolalar bo'yicha mualliflar bilan yozishmalar.
 *
 * "O'qilmagan" — muallif tomonidan (yuboruvchi yoki kabinetga bog'langan hammuallif)
 * yozilgan va tahririyat hali ochmagan xabarlar (messages.read_at = null).
 * Filtrlar: all — hammasi, unread — javob kutayotganlar, mine — mas'ul muharriri men.
 */
class StaffInbox
{
    public const PER_PAGE = 20;

    public const SCOPES = ['all', 'unread', 'mine'];

    /**
     * @param  array<string, mixed>  $input
     * @return array{scope: string, q: string}
     */
    public static function filters(array $input): array
    {
        $scope = $input['scope'] ?? 'all';
        $q = $input['q'] ?? '';

        return [
            'scope' => is_string($scope) && in_array($scope, self::SCOPES, true) ? $scope : 'all',
            'q' => is_string($q) ? mb_substr(trim($q), 0, 100) : '',
        ];
    }

    /**
     * Muallif tomonidan yozilgan o'qilmagan xabarlar (yozishma kanali).
     *
     * @return Builder<Message>
     */
    public static function unreadFromAuthors(): Builder
    {
        return Message::query()
            ->where('channel', MessageChannel::AuthorEditor->value)
            ->whereNull('read_at')
            ->where(fn (Builder $q) => $q
                ->whereExists(fn (QueryBuilder $s) => $s->from('articles')
                    ->whereColumn('articles.id', 'messages.article_id')
                    ->whereColumn('articles.submitter_id', 'messages.sender_id'))
                ->orWhereExists(fn (QueryBuilder $s) => $s->from('article_authors')
                    ->whereColumn('article_authors.article_id', 'messages.article_id')
                    ->whereColumn('article_authors.user_id', 'messages.sender_id')));
    }

    /**
     * @param  array{scope: string, q: string}  $filters
     * @return array{data: array<int, array<string, mixed>>, meta: array{currentPage: int, lastPage: int, total: int, from: int|null, to: int|null}}
     */
    public function conversations(User $staff, array $filters, int $page = 1): array
    {
        $last = Message::query()
            ->selectRaw('article_id, max(id) as last_id')
            ->where('channel', MessageChannel::AuthorEditor->value)
            ->groupBy('article_id');

        $query = Article::query()
            ->joinSub($last, 'lm', 'lm.article_id', '=', 'articles.id')
            ->select('articles.*', 'lm.last_id')
            ->where('articles.status', '!=', ArticleStatus::Draft->value)
            ->with(['submitter:id,name', 'handlingEditor:id,name']);

        if ($filters['scope'] === 'mine') {
            $query->where('articles.handling_editor_id', $staff->id);
        } elseif ($filters['scope'] === 'unread') {
            $query->whereIn('articles.id', self::unreadFromAuthors()->select('article_id'));
        }

        if ($filters['q'] !== '') {
            $term = $filters['q'];
            $like = '%'.mb_strtolower(str_replace(['%', '_'], '', $term)).'%';

            $query->where(function (Builder $q) use ($term, $like) {
                if (preg_match('/^(?:\d{4}-)?0*(\d+)$/', $term, $m) === 1) {
                    $q->orWhere('articles.id', (int) $m[1]);
                }

                $q->orWhereRaw('lower(articles.search_text) like ?', [$like])
                    ->orWhereHas('submitter', fn (Builder $u) => $u->whereRaw('lower(name) like ?', [$like]));
            });
        }

        /** @var LengthAwarePaginator<int, Article> $paginator */
        $paginator = $query->orderByDesc('lm.last_id')->paginate(self::PER_PAGE, page: $page);

        $articles = collect($paginator->items());
        $ids = $articles->map(fn (Article $a): int => $a->id)->all();

        $lastMessages = Message::query()
            ->whereIn('id', $articles->map(fn (Article $a): int => (int) $a->getAttribute('last_id'))->all())
            ->with('sender:id,name')
            ->get()
            ->keyBy('article_id');

        $unread = self::unreadFromAuthors()
            ->whereIn('article_id', $ids)
            ->selectRaw('article_id, count(*) as total')
            ->groupBy('article_id')
            ->pluck('total', 'article_id');

        return [
            'data' => $articles->map(function (Article $article) use ($lastMessages, $unread, $staff): array {
                /** @var Message|null $message */
                $message = $lastMessages->get($article->id);
                $fromAuthor = $message !== null && ($message->sender_id === $article->submitter_id
                    || $article->authors()->where('user_id', $message->sender_id)->exists());

                return [
                    'uuid' => $article->uuid,
                    'code' => EditorialWorkspace::code($article),
                    'title' => $article->title,
                    'author' => $article->submitter->name,
                    'editor' => $article->handlingEditor?->name,
                    'isMine' => $article->handling_editor_id === $staff->id,
                    'statusLabel' => $article->status->label(),
                    'statusGroup' => $article->status->group(),
                    'unread' => (int) ($unread[$article->id] ?? 0),
                    'last' => $message !== null ? [
                        'body' => Str::limit($message->body, 120),
                        'sender' => $message->sender->name,
                        'fromAuthor' => $fromAuthor,
                        'mine' => $message->sender_id === $staff->id,
                        'hasAttachment' => $message->attachment_path !== null,
                        'createdAt' => $message->created_at->toIso8601String(),
                    ] : null,
                ];
            })->values()->all(),
            'meta' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }

    /**
     * @return array{all: int, unread: int, mine: int}
     */
    public function counts(User $staff): array
    {
        $withMessages = fn (): Builder => Article::query()
            ->where('status', '!=', ArticleStatus::Draft->value)
            ->whereHas('messages', fn (Builder $m) => $m->where('channel', MessageChannel::AuthorEditor->value));

        return [
            'all' => $withMessages()->count(),
            'unread' => (int) self::unreadFromAuthors()->distinct()->count('article_id'),
            'mine' => $withMessages()->where('handling_editor_id', $staff->id)->count(),
        ];
    }

    /** Sidebar raqami: muallif tomonidan yozilgan va ochilmagan xabarlar */
    public static function unreadTotal(): int
    {
        return self::unreadFromAuthors()->count();
    }
}
