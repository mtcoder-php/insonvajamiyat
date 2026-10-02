<?php

namespace App\Services\Cabinet;

use App\Enums\ArticleStatus;
use App\Enums\MessageChannel;
use App\Models\Article;
use App\Models\Message;
use App\Models\User;
use App\Services\Editorial\EditorialWorkspace;
use App\Services\Messages\ArticleMessageService;
use Illuminate\Support\Str;

/**
 * Muallif kabineti → "Xabarlar": maqolalar bo'yicha tahririyat bilan yozishmalar ro'yxati.
 * Yozishma bitta maqolaga bog'langan (messages, channel author_editor).
 */
class AuthorInbox
{
    public function __construct(private readonly ArticleMessageService $messages) {}

    /**
     * Muallifning (yuborilgan) maqolalari: oxirgi xabar, o'qilmaganlar soni.
     * Avval xabari borlar (eng yangisi tepada), keyin xabarsizlar.
     *
     * @return array<int, array<string, mixed>>
     */
    public function conversations(User $user): array
    {
        $articles = Article::query()
            ->ownedBy($user)
            ->where('status', '!=', ArticleStatus::Draft->value)
            ->get();

        $last = Message::query()
            ->whereIn('article_id', $articles->modelKeys())
            ->where('channel', MessageChannel::AuthorEditor->value)
            ->with('sender')
            ->orderByDesc('id')
            ->get()
            ->unique('article_id')
            ->keyBy('article_id');

        return $articles
            ->map(function (Article $article) use ($last, $user): array {
                /** @var Message|null $message */
                $message = $last->get($article->id);
                $fromAuthor = $message !== null && $this->messages->isAuthorSide($article, $message->sender);

                return [
                    'uuid' => $article->uuid,
                    'code' => EditorialWorkspace::code($article),
                    'title' => $article->title,
                    'status' => $article->status->value,
                    'statusLabel' => $article->status->label(),
                    'statusGroup' => $article->status->group(),
                    'unread' => $message !== null ? $this->messages->unreadCount($article, $user) : 0,
                    'last' => $message !== null ? [
                        'body' => Str::limit($message->body, 120),
                        'mine' => $message->sender_id === $user->id,
                        'fromEditorial' => ! $fromAuthor,
                        'createdAt' => $message->created_at->toIso8601String(),
                    ] : null,
                    'sortKey' => $message?->created_at->getTimestamp() ?? 0,
                    'submittedAt' => $article->submitted_at?->toIso8601String(),
                    'url' => route('cabinet.messages.index', ['article' => $article->uuid]),
                    'articleUrl' => route('cabinet.articles.show', $article->uuid),
                ];
            })
            ->sortByDesc(fn (array $c): int => $c['sortKey'])
            ->values()
            ->all();
    }

    /** Barcha yozishmalardagi o'qilmagan xabarlar */
    public function unreadTotal(User $user): int
    {
        return (int) collect($this->conversations($user))->sum('unread');
    }
}
