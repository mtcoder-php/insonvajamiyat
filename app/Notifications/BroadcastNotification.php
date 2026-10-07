<?php

namespace App\Notifications;

use App\Models\Broadcast;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Ommaviy xabar (e'lon): bildirishnoma + (tanlangan bo'lsa va email tasdiqlangan bo'lsa) email.
 * Har bir qabul qiluvchi uchun navbatda alohida yuboriladi.
 */
class BroadcastNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Broadcast $broadcast) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->broadcast->send_email && $notifiable instanceof User && $notifiable->email_verified_at !== null) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $notifiable instanceof User ? $notifiable->name : null;
        $mail = (new MailMessage)
            ->subject($this->broadcast->subject)
            ->greeting($name !== null ? __('Assalomu alaykum, :name!', ['name' => $name]) : __('Assalomu alaykum!'));

        foreach (preg_split('/\R{2,}/', trim($this->broadcast->body)) ?: [] as $paragraph) {
            $mail->line(trim($paragraph));
        }

        return $mail
            ->action(__('Saytni ochish'), url('/'))
            ->salutation(__("Hurmat bilan,\n«Inson va Jamiyat» tahririyati"));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'broadcast',
            'broadcast_uuid' => $this->broadcast->uuid,
            'title' => $this->broadcast->subject,
            'body' => Str::limit($this->broadcast->body, 1000),
            'url' => null,
        ];
    }
}
