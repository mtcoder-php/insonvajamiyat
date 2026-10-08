<?php

namespace App\Notifications\Web;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Saytdagi "Aloqa" formasidan tahririyat pochtasiga xat (navbat orqali).
 * "Javob berish" — to'g'ridan-to'g'ri yuboruvchining emailiga (Reply-To).
 */
class ContactMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $subject,
        public readonly string $body,
        public readonly ?string $ip = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('[Sayt] '.($this->subject ?? 'Aloqa formasidan xabar').' — '.$this->name)
            ->replyTo($this->email, $this->name)
            ->greeting('Saytdagi «Aloqa» formasidan yangi xabar')
            ->line('**Yuboruvchi:** '.$this->name.' <'.$this->email.'>');

        if ($this->subject !== null) {
            $mail->line('**Mavzu:** '.$this->subject);
        }

        foreach (preg_split('/\R{2,}/u', $this->body) ?: [] as $paragraph) {
            $mail->line(trim($paragraph));
        }

        return $mail
            ->line('Javob berish uchun xatga shunchaki javob yozing — u yuboruvchiga boradi.')
            ->salutation('IP: '.($this->ip ?? '—'));
    }
}
