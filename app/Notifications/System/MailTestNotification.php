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
            ->subject(__(':journal — test xat', ['journal' => $journal]))
            ->greeting(__('Assalomu alaykum!'))
            ->line(__('Bu — tizim sozlamalaridagi pochta (SMTP) ulanishini tekshirish uchun yuborilgan test xat.'))
            ->line(__("Agar uni olgan bo'lsangiz, saytdan yuboriladigan barcha xatlar (ro'yxatdan o'tish, parolni tiklash, maqola holati) to'g'ri yetib boradi."))
            ->line(__('Yuborgan: :name · :date', ['name' => $this->sentBy, 'date' => now()->format('d.m.Y H:i')]))
            ->action(__('Saytni ochish'), url('/'));
    }
}
