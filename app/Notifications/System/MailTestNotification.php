<?php

namespace App\Notifications\System;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Admin → Tizim sozlamalari → Pochta: "Test xat yuborish".
 * Navbatsiz (notifyNow) yuboriladi — xato bo'lsa darhol ko'rsatiladi.
 */
class MailTestNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $sentBy) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = config('journal.name');
        $journal = is_string($name) ? $name : 'Inson va Jamiyat';

        return (new MailMessage)
            ->subject($journal.' — test xat')
            ->greeting('Assalomu alaykum!')
            ->line('Bu — tizim sozlamalaridagi pochta (SMTP) ulanishini tekshirish uchun yuborilgan test xat.')
            ->line('Agar uni olgan bo\'lsangiz, saytdan yuboriladigan barcha xatlar (ro\'yxatdan o\'tish, parolni tiklash, maqola holati) to\'g\'ri yetib boradi.')
            ->line('Yuborgan: '.$this->sentBy.' · '.now()->format('d.m.Y H:i'))
            ->action('Saytni ochish', url('/'));
    }
}
