<?php

namespace App\Notifications;

use App\Models\Article;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Maqola bo'yicha bildirishnoma (bazada + email):
 *   message     — yozishmada yangi xabar;
 *   decision    — tahririyat qarori (tuzatish / qabul / rad);
 *   resubmitted — muallif tuzatilgan versiyani yubordi.
 *
 * Qabul qiluvchi muallif bo'lsa havola kabinetga, xodim bo'lsa admin panelga olib boradi.
 */
class ArticleUpdateNotification extends Notification
{
    public const MESSAGE = 'message';

    public const DECISION = 'decision';

    public const RESUBMITTED = 'resubmitted';

    public readonly string $headline;

    /**
     * @param  array<mixed>|string|null  $headline  __() natijasi
     */
    public function __construct(
        public readonly Article $article,
        public readonly string $kind,
        array|string|null $headline,
        public readonly ?string $body = null,
        public readonly bool $toStaff = false,
    ) {
        $this->headline = is_string($headline) ? $headline : '';
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function url(): string
    {
        return $this->toStaff
            ? route('admin.articles.index', ['queue' => 'all', 'article' => $this->article->uuid])
            : route('cabinet.articles.show', $this->article->uuid);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $notifiable instanceof User ? $notifiable->name : null;
        $mail = (new MailMessage)
            ->subject(__(':headline — :app', ['headline' => $this->headline, 'app' => config('app.name')]))
            ->greeting($name !== null ? __('Assalomu alaykum, :name!', ['name' => $name]) : __('Assalomu alaykum!'))
            ->line($this->headline.'.')
            ->line(__('Maqola: «:title»', ['title' => $this->article->title]));

        if ($this->body !== null && $this->body !== '') {
            $mail->line(Str::limit($this->body, 600));
        }

        return $mail
            ->action($this->toStaff ? __("Admin panelda ko'rish") : __("Kabinetda ko'rish"), $this->url())
            ->salutation(__("Hurmat bilan,\n«Inson va Jamiyat» tahririyati"));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind,
            'article_uuid' => $this->article->uuid,
            'title' => $this->headline,
            'article_title' => $this->article->title,
            'body' => $this->body !== null ? Str::limit($this->body, 200) : null,
            'url' => $this->url(),
        ];
    }
}
