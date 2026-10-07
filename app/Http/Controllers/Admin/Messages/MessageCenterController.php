<?php

namespace App\Http\Controllers\Admin\Messages;

use App\Enums\ArticleStatus;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\User;
use App\Services\Editorial\EditorialWorkspace;
use App\Services\Messages\ArticleMessageService;
use App\Services\Messages\BroadcastService;
use App\Services\Messages\StaffInbox;
use App\Services\Notifications\NotificationCenter;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin → Xabarlar:
 *   ?tab=messages      — maqolalar bo'yicha mualliflar bilan yozishmalar (?scope=all|unread|mine, ?q, ?article);
 *   ?tab=broadcast     — ommaviy xabar yuborish va tarix (users.manage);
 *   ?tab=notifications — xodimning bildirishnomalari (?unread=1).
 * Yozishma ochilganda muallif xabarlari o'qilgan deb belgilanadi.
 */
class MessageCenterController extends Controller
{
    public const TABS = ['messages', 'broadcast', 'notifications'];

    public function __invoke(
        Request $request,
        StaffInbox $inbox,
        ArticleMessageService $messages,
        BroadcastService $broadcasts,
        NotificationCenter $notifications,
    ): Response {
        /** @var User $user */
        $user = $request->user();
        $canBroadcast = $user->can(PermissionName::UsersManage->value);

        $tabs = $canBroadcast ? self::TABS : ['messages', 'notifications'];
        $tab = $request->string('tab')->toString();
        $tab = in_array($tab, $tabs, true) ? $tab : 'messages';

        $filters = StaffInbox::filters($request->query->all());
        $onlyUnread = $request->boolean('unread');

        $thread = null;

        if ($tab === 'messages') {
            $uuid = $request->string('article')->toString();
            $article = $uuid !== ''
                ? Article::query()->where('uuid', $uuid)->where('status', '!=', ArticleStatus::Draft->value)->first()
                : null;

            if ($article !== null && Gate::allows('view', $article)) {
                $messages->markRead($article, $user);
                $article->loadMissing(['submitter:id,name,email', 'handlingEditor:id,name']);

                $thread = [
                    'uuid' => $article->uuid,
                    'code' => EditorialWorkspace::code($article),
                    'title' => $article->title,
                    'statusLabel' => $article->status->label(),
                    'statusGroup' => $article->status->group(),
                    'author' => $article->submitter->name,
                    'authorEmail' => $article->submitter->email,
                    'editor' => $article->handlingEditor?->name,
                    'articleUrl' => route('admin.articles.index', ['queue' => 'all', 'article' => $article->uuid]),
                    'items' => $messages->thread($article, $user),
                    'sendUrl' => Gate::allows('message', $article)
                        ? route('admin.articles.messages.store', $article->uuid)
                        : null,
                ];
            }
        }

        $page = $tab === 'notifications' ? $notifications->paginate($user, $onlyUnread) : null;

        return Inertia::render('admin/messages/Index', [
            'tabs' => $tabs,
            'filters' => [...$filters, 'tab' => $tab, 'article' => $thread['uuid'] ?? null, 'unread' => $onlyUnread],
            'conversations' => $tab === 'messages'
                ? fn (): array => $inbox->conversations($user, $filters, max(1, $request->integer('page', 1)))
                : null,
            'counts' => fn (): array => $inbox->counts($user),
            'thread' => $thread,
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
            'audiences' => $tab === 'broadcast' ? fn (): array => $broadcasts->audiences() : null,
            'broadcasts' => $tab === 'broadcast' ? fn (): array => $broadcasts->history() : null,
            'urls' => [
                'index' => route('admin.messages.index'),
                'broadcast' => $canBroadcast ? route('admin.messages.broadcasts.store') : null,
                'readAll' => route('notifications.read-all'),
            ],
        ]);
    }
}
