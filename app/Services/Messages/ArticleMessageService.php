<?php

namespace App\Services\Messages;

use App\Enums\MessageChannel;
use App\Models\Article;
use App\Models\Message;
use App\Models\User;
use App\Notifications\ArticleUpdateNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Muallif ↔ tahririyat yozishmasi (messages, channel = author_editor).
 *
 * Tomonlar: maqola mualliflari (yuboruvchi + kabinetga bog'langan hammualliflar) va
 * tahririyat xodimlari. read_at — qarshi tomon xabarni o'qigan vaqt.
 * Yangi xabar qarshi tomonga bildirishnoma (baza + email) bilan yetkaziladi:
 * muallifga — yuboruvchi; tahririyatga — mas'ul muharrir (biriktirilgan bo'lsa).
 */
class ArticleMessageService
{
    public const DISK = 'local';

    public const CHANNEL = MessageChannel::AuthorEditor;

    public function send(Article $article, User $sender, string $body, ?UploadedFile $attachment = null): Message
    {
        $fromAuthor = $this->isAuthorSide($article, $sender);

        $message = DB::transaction(function () use ($article, $sender, $body, $attachment): Message {
            $data = [
                'channel' => self::CHANNEL,
                'sender_id' => $sender->id,
                'body' => $body,
            ];

            if ($attachment !== null) {
                $extension = strtolower($attachment->getClientOriginalExtension()) ?: 'bin';
                $path = $attachment->storeAs("messages/{$article->uuid}", Str::uuid()->toString().'.'.$extension, self::DISK);

                if ($path === false) {
                    throw new RuntimeException('Message attachment could not be stored.');
                }

                $data['attachment_path'] = $path;
                $data['attachment_name'] = $attachment->getClientOriginalName();
            }

            return $article->messages()->create($data);
        });

        $recipient = $fromAuthor ? $article->handlingEditor : $article->submitter;

        if ($recipient !== null && $recipient->id !== $sender->id) {
            $recipient->notifyInLocale(fn (): ArticleUpdateNotification => new ArticleUpdateNotification(
                $article,
                ArticleUpdateNotification::MESSAGE,
                $fromAuthor ? __('Muallifdan yangi xabar') : __('Tahririyatdan yangi xabar'),
                $body,
                toStaff: $fromAuthor,
            ));
        }

        return $message;
    }

    /**
     * Yozishma ro'yxati. Muallif uchun xodim nomi o'rniga "Tahririyat" yoziladi.
     *
     * @return array<int, array<string, mixed>>
     */
    public function thread(Article $article, User $viewer): array
    {
        $authorIds = $this->authorIds($article);
        $viewerIsAuthor = in_array($viewer->id, $authorIds, true);

        return $article->messages()
            ->where('channel', self::CHANNEL->value)
            ->with('sender')
            ->get()
            ->map(function (Message $message) use ($article, $authorIds, $viewer, $viewerIsAuthor): array {
                $fromAuthor = in_array($message->sender_id, $authorIds, true);

                return [
                    'id' => $message->id,
                    'body' => $message->body,
                    'side' => $fromAuthor ? 'author' : 'editorial',
                    'mine' => $message->sender_id === $viewer->id,
                    'sender' => $fromAuthor || ! $viewerIsAuthor
                        ? $message->sender->name
                        : __('Tahririyat'),
                    'attachmentName' => $message->attachment_name,
                    'attachmentUrl' => $message->attachment_path !== null
                        ? route('cabinet.articles.messages.attachment', [$article->uuid, $message->id])
                        : null,
                    'readAt' => $message->read_at?->toIso8601String(),
                    'createdAt' => $message->created_at->toIso8601String(),
                ];
            })
            ->all();
    }

    /**
     * Qarshi tomon xabarlarini o'qilgan deb belgilash va shu maqola bo'yicha
     * foydalanuvchining bildirishnomalarini yopish.
     */
    public function markRead(Article $article, User $viewer): void
    {
        $authorIds = $this->authorIds($article);
        $query = $article->messages()
            ->where('channel', self::CHANNEL->value)
            ->whereNull('read_at');

        if (in_array($viewer->id, $authorIds, true)) {
            $query->whereNotIn('sender_id', $authorIds);
        } else {
            $query->whereIn('sender_id', $authorIds);
        }

        $query->update(['read_at' => now()]);

        $viewer->unreadNotifications()
            ->where('type', ArticleUpdateNotification::class)
            ->get()
            ->filter(fn (DatabaseNotification $n): bool => ($n->data['article_uuid'] ?? null) === $article->uuid)
            ->each(fn (DatabaseNotification $n) => $n->markAsRead());
    }

    /** Qarshi tomondan kelgan o'qilmagan xabarlar soni */
    public function unreadCount(Article $article, User $viewer): int
    {
        $authorIds = $this->authorIds($article);
        $query = $article->messages()
            ->where('channel', self::CHANNEL->value)
            ->whereNull('read_at');

        return in_array($viewer->id, $authorIds, true)
            ? $query->whereNotIn('sender_id', $authorIds)->count()
            : $query->whereIn('sender_id', $authorIds)->count();
    }

    public function attachment(Message $message): StreamedResponse
    {
        abort_unless(
            $message->attachment_path !== null && Storage::disk(self::DISK)->exists($message->attachment_path),
            404,
        );

        return Storage::disk(self::DISK)->download($message->attachment_path, $message->attachment_name);
    }

    public function isAuthorSide(Article $article, User $user): bool
    {
        return in_array($user->id, $this->authorIds($article), true);
    }

    /**
     * Muallif tomoni: yuboruvchi va kabinetga bog'langan hammualliflar.
     *
     * @return array<int, int>
     */
    private function authorIds(Article $article): array
    {
        $ids = $article->authors()->whereNotNull('user_id')->pluck('user_id')->map(fn (mixed $id): int => (int) $id)->all();
        $ids[] = $article->submitter_id;

        return array_values(array_unique($ids));
    }
}
