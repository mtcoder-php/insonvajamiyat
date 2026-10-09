<?php

namespace App\Jobs;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Notifications\Newsletter\NewsletterCampaignNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Obuna xatini tarqatish: tasdiqlangan obunachilar 200 tadan olinadi, har biriga alohida
 * NewsletterCampaignNotification (o'zi navbatga tushadi, obunachi tilida).
 * Holat va soni newsletter_campaigns jadvalida.
 */
class SendNewsletterCampaign implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public readonly NewsletterCampaign $campaign) {}

    public function handle(): void
    {
        $campaign = $this->campaign;
        $campaign->forceFill(['status' => NewsletterCampaign::SENDING])->save();

        $sent = 0;

        $campaign->recipients()->chunkById(200, function (Collection $subscribers) use ($campaign, &$sent): void {
            /** @var NewsletterSubscriber $subscriber */
            foreach ($subscribers as $subscriber) {
                Notification::route('mail', $subscriber->email)
                    ->notify((new NewsletterCampaignNotification($campaign, $subscriber))->locale($subscriber->locale));
            }

            $sent += $subscribers->count();
            $campaign->forceFill(['sent_count' => $sent])->save();
        });

        $campaign->forceFill([
            'status' => NewsletterCampaign::SENT,
            'sent_count' => $sent,
            'sent_at' => now(),
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        $this->campaign->forceFill([
            'status' => NewsletterCampaign::FAILED,
            'error' => mb_substr((string) $exception?->getMessage(), 0, 500),
        ])->save();
    }
}
