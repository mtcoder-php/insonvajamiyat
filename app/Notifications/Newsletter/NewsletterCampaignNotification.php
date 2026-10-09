<?php

namespace App\Notifications\Newsletter;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mime\Email;

/**
 * Obunachiga jurnal xati. Har bir xatda obunadan chiqish havolasi va pochta dasturlari
 * uchun List-Unsubscribe (+ RFC 8058 One-Click) sarlavhalari bor — Gmail/Yandex talabi.
 * Navbat orqali, har bir obunachiga alohida (SendNewsletterCampaign tarqatadi).
 */
class NewsletterCampaignNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300];

    public function __construct(
        public readonly NewsletterCampaign $campaign,
        public readonly NewsletterSubscriber $subscriber,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Navbatda turgan paytda obunadan chiqqan bo'lsa — yuborilmaydi.
     */
    public function shouldSend(object $notifiable, string $channel): bool
    {
        return $this->subscriber->fresh()?->isConfirmed() ?? false;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $campaign = $this->campaign;
        $unsubscribe = $this->subscriber->unsubscribeUrl();
        $oneClick = $this->subscriber->oneClickUnsubscribeUrl();

        $mail = (new MailMessage)
            ->subject($campaign->subject)
            ->greeting(__('Assalomu alaykum!'));

        foreach (preg_split('/\R{2,}/u', trim($campaign->body)) ?: [] as $paragraph) {
            $mail->line(trim($paragraph));
        }

        if ($campaign->button_label !== null && $campaign->button_url !== null) {
            $mail->action($campaign->button_label, $campaign->button_url);
        }

        return $mail
            ->salutation(__("Hurmat bilan,\n«Inson va Jamiyat» tahririyati"))
            ->line(__("Siz bu xatni jurnal saytida yangiliklarga obuna bo'lganingiz uchun oldingiz. [Obunadan chiqish](:url)", ['url' => $unsubscribe]))
            ->withSymfonyMessage(function (Email $message) use ($oneClick, $unsubscribe): void {
                $message->getHeaders()->addTextHeader('List-Unsubscribe', "<{$oneClick}>, <{$unsubscribe}>");
                $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
    }
}
