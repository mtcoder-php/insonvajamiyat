<?php

namespace App\Services\Newsletter;

use App\Models\NewsletterSubscriber;
use App\Notifications\Newsletter\ConfirmSubscriptionNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Yangiliklarga obuna mantiqi (double opt-in).
 *
 *  subscribe   — yangi yoki tasdiqlanmagan manzilga tasdiqlash xati yuboradi; takroriy obuna
 *                xatolik emas (idempotent). Javob har doim bir xil — manzil bazada bormi,
 *                tashqaridan bilib bo'lmaydi. Xat ko'pi bilan RESEND_AFTER_MINUTES da bir marta.
 *  confirm     — havola bosilganda obuna faollashadi.
 *  unsubscribe — obunadan chiqish (qayta obuna bo'lsa, yana tasdiqlash kerak bo'ladi).
 */
class NewsletterService
{
    /** Tasdiqlash xatini qayta yuborish oralig'i (daqiqa) */
    public const RESEND_AFTER_MINUTES = 10;

    public function subscribe(string $email, string $locale, ?string $ip): NewsletterSubscriber
    {
        $email = Str::lower(trim($email));

        $subscriber = NewsletterSubscriber::firstOrNew(['email' => $email]);

        if (! $subscriber->exists) {
            $subscriber->fill([
                'token' => Str::random(64),
                'ip' => $ip,
            ]);
        }

        // Allaqachon faol obunachi — hech narsa o'zgarmaydi, xat yuborilmaydi
        if ($subscriber->isConfirmed()) {
            return $subscriber;
        }

        // Obunadan chiqqan manzil qayta obuna bo'lmoqda — yangidan tasdiqlash talab qilinadi
        if ($subscriber->unsubscribed_at !== null) {
            $subscriber->forceFill(['unsubscribed_at' => null, 'confirmed_at' => null]);
        }

        $subscriber->locale = $locale;

        $shouldSend = $subscriber->confirmation_sent_at === null
            || $subscriber->confirmation_sent_at->lt(now()->subMinutes(self::RESEND_AFTER_MINUTES));

        if ($shouldSend) {
            $subscriber->forceFill(['confirmation_sent_at' => now()]);
        }

        $subscriber->save();

        if ($shouldSend) {
            Notification::route('mail', $subscriber->email)
                ->notify((new ConfirmSubscriptionNotification($subscriber))->locale($subscriber->locale));
        }

        return $subscriber;
    }

    /**
     * Tasdiqlash havolasi. Natija: 'confirmed' | 'already' | 'invalid'.
     */
    public function confirm(string $token): string
    {
        $subscriber = $this->find($token);

        if ($subscriber === null) {
            return 'invalid';
        }

        if ($subscriber->isConfirmed()) {
            return 'already';
        }

        $subscriber->forceFill([
            'confirmed_at' => now(),
            'unsubscribed_at' => null,
        ])->save();

        return 'confirmed';
    }

    /**
     * Obunadan chiqish. Natija: 'unsubscribed' | 'already' | 'invalid'.
     */
    public function unsubscribe(string $token): string
    {
        $subscriber = $this->find($token);

        if ($subscriber === null) {
            return 'invalid';
        }

        if ($subscriber->unsubscribed_at !== null) {
            return 'already';
        }

        $subscriber->forceFill(['unsubscribed_at' => now()])->save();

        return 'unsubscribed';
    }

    public function find(string $token): ?NewsletterSubscriber
    {
        if (strlen($token) !== 64) {
            return null;
        }

        return NewsletterSubscriber::query()->where('token', $token)->first();
    }
}
