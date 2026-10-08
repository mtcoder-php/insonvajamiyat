<?php

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Foydalanuvchining bildirishnomalari (notifications jadvali):
 * header'dagi qo'ng'iroqcha (har sahifada ulashiladi), kabinetdagi "Xabarlar" sahifasi,
 * o'qilgan deb belgilash.
 */
class NotificationCenter
{
    /** Header ro'yxatidagi bildirishnomalar soni */
    public const LATEST = 8;

    /**
     * @return array{unread: int, items: array<int, array<string, mixed>>}
     */
    public function summary(User $user, int $limit = self::LATEST): array
    {
        return [
            'unread' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()
                ->limit($limit)
                ->get()
                ->map(fn (DatabaseNotification $n): array => self::item($n))
                ->all(),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, DatabaseNotification>
     */
    public function paginate(User $user, bool $onlyUnread = false, int $perPage = 15): LengthAwarePaginator
    {
        return ($onlyUnread ? $user->unreadNotifications() : $user->notifications())
            ->paginate($perPage, pageName: 'npage')
            ->withQueryString();
    }

    /**
     * O'qilgan deb belgilaydi va bildirishnoma havolasini qaytaradi (faqat shu sayt ichida).
     */
    public function open(User $user, string $id): ?string
    {
        /** @var DatabaseNotification|null $notification */
        $notification = $user->notifications()->whereKey($id)->first();

        if ($notification === null) {
            return null;
        }

        $notification->markAsRead();

        return self::safeUrl($notification->data['url'] ?? null);
    }

    public function markAllRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function item(DatabaseNotification $notification): array
    {
        /** @var array<string, mixed> $data */
        $data = (array) $notification->data;
        $string = fn (string $key): ?string => is_string($data[$key] ?? null) && $data[$key] !== '' ? $data[$key] : null;

        return [
            'id' => (string) $notification->id,
            'kind' => $string('kind') ?? 'info',
            'title' => $string('title') ?? 'Bildirishnoma',
            'articleTitle' => $string('article_title'),
            'body' => $string('body') ?? $string('message'),
            'url' => self::safeUrl($data['url'] ?? null),
            'openUrl' => route('notifications.read', (string) $notification->id),
            'read' => $notification->read_at !== null,
            'createdAt' => $notification->created_at?->toIso8601String(),
        ];
    }

    /**
     * Ochiq yo'naltirishdan (open redirect) himoya: faqat nisbiy yoki shu sayt manzili.
     */
    public static function safeUrl(mixed $url): ?string
    {
        // "\\evil.com" va "/\\evil.com" brauzerda "//evil.com" deb talqin qilinadi
        if (! is_string($url) || $url === '' || str_contains($url, '\\') || preg_match('/[\x00-\x1F]/', $url) === 1) {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $requestHost = request()->getHost();

        return is_string($host) && ($host === $appHost || $host === $requestHost) ? $url : null;
    }
}
