<?php

namespace App\Services;

use App\Models\NewsletterSubscriber;
use Illuminate\Support\Str;

/**
 * Yangiliklarga obuna mantiqi.
 *
 * Takroriy obuna xatolik emas (idempotent): email allaqachon bo'lsa, u qayta
 * faollashtiriladi. Tasdiqlash xati (double opt-in) yuborish — xabarnomalar
 * moduli bilan qo'shiladi; hozircha confirmed_at bo'sh qoladi.
 */
class NewsletterService
{
    public function subscribe(string $email, string $locale, ?string $ip): NewsletterSubscriber
    {
        $email = Str::lower(trim($email));

        $subscriber = NewsletterSubscriber::firstOrNew(['email' => $email]);

        if (! $subscriber->exists) {
            $subscriber->fill([
                'locale' => $locale,
                'token' => Str::random(64),
                'ip' => $ip,
            ]);
        }

        $subscriber->unsubscribed_at = null;
        $subscriber->save();

        return $subscriber;
    }
}
