<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\User;
use App\Services\Cabinet\AuthorInbox;
use App\Services\Editorial\EditorialWorkspace;
use App\Services\Messages\ArticleMessageService;
use App\Services\Notifications\NotificationCenter;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Muallif kabineti → "Xabarlar":
 *   ?tab=messages      — maqolalar bo'yicha tahririyat bilan yozishmalar (chapda ro'yxat, o'ngda yozishma);
 *   ?tab=notifications — barcha bildirishnomalar (?unread=1 — faqat o'qilmaganlar).
 * Yozishma ochilganda xabarlar va shu maqola bildirishnomalari o'qilgan bo'ladi.
 */
class MessagesController extends Controller
{
    public const TABS = ['messages', 'notifications'];

    public function __invoke(
        Request $request,
        AuthorInbox $inbox,
        ArticleMessageService $messages,
        NotificationCenter $notifications,
    ): Response {
        /** @var User $user */
        $user = $request->user();
        $tab = $request->string('tab')->toString();
        $tab = in_array($tab, self::TABS, true) ? $tab : 'messages';
        $onlyUnread = $request->boolean('unread');

        $conversations = $inbox->conversations($user);

        $selected = null;
        $uuid = $request->string('article')->toString();

        if ($tab === 'messages') {
            $first = $conversations->first();
            $selectedUuid = $uuid !== '' ? $uuid : (is_array($first) ? $first['uuid'] : null);
            $article = is_string($selectedUuid) ? Article::query()->where('uuid', $selectedUuid)->first() : null;

            if ($article !== null && Gate::allows('view', $article)) {
                $messages->markRead($article, $user);
                $selected = [
                    'uuid' => $article->uuid,
                    'code' => EditorialWorkspace::code($article),
                    'title' => $article->title,
                    'statusLabel' => $article->status->label(),
                    'articleUrl' => route('cabinet.articles.show', $article->uuid),
                    'items' => $messages->thread($article, $user),
                    'sendUrl' => Gate::allows('message', $article)
                        ? route('cabinet.articles.messages.store', $article->uuid)
                        : null,
                ];

                // O'qilgan holatni ro'yxatda ham yangilash
                $conversations = $inbox->conversations($user);
            }
        }

        $page = $tab === 'notifications' ? $notifications->paginate($user, $onlyUnread) : null;

        return Inertia::render('cabinet/Messages', [
            'filters' => ['tab' => $tab, 'article' => $selected['uuid'] ?? null, 'unread' => $onlyUnread],
            'conversations' => $conversations->map(fn (array $c): array => collect($c)->except('sortKey')->all())->all(),
            'thread' => $selected,
            'notificationsPage' => $page !== null ? [
                'data' => array_map(fn (DatabaseNotification $n): array => NotificationCenter::item($n), $page->items()),
                'meta' => [
                    'currentPage' => $page->currentPage(),
                    'lastPage' => $page->lastPage(),
                    'total' => $page->total(),
                    'from' => $page->firstItem(),
                    'to' => $page->lastItem(),
                ],
            ] : null,
            'unreadMessages' => (int) $conversations->sum('unread'),
        ]);
    }
}
