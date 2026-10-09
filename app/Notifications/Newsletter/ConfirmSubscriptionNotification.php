<?php

namespace App\Notifications\Newsletter;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Obunani tasdiqlash xati (double opt-in): havola bosilmaguncha jurnal xatlari yuborilmaydi.
 * Obunachi tanlagan tilda (NewsletterService ->locale()) va navbat orqali yuboriladi.
 */
class ConfirmSubscriptionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly NewsletterSubscriber $subscriber,
    ) {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Obunani tasdiqlang — :app', ['app' => config('app.name')]))
            ->greeting(__('Assalomu alaykum!'))
            ->line(__("Ushbu elektron pochta manzili «Inson va Jamiyat» ilmiy jurnali yangiliklariga obuna bo'lish uchun ko'rsatildi.")
            )
            ->line(__('Obunani tasdiqlash uchun quyidagi tugmani bosing — shundan keyin yangi sonlar va jurnal yangiliklarini yuboramiz.'))
            ->action(__('Obunani tasdiqlash'), $this->subscriber->confirmUrl())
            ->line(__("Agar siz obuna bo'lmagan bo'lsangiz, bu xatga e'tibor bermang — tasdiqlanmagan manzilga boshqa xat yuborilmaydi.")
            )
            ->salutation(__("Hurmat bilan,\n«Inson va Jamiyat» tahririyati"));
    }
}
