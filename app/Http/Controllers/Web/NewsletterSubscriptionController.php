<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\SubscribeNewsletterRequest;
use App\Services\NewsletterService;
use App\Support\Seo\SeoMeta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Yangiliklarga obuna: footer formasi, tasdiqlash havolasi va obunadan chiqish.
 *
 *  POST newsletter                          — obuna (tasdiqlash xati yuboriladi)
 *  GET  newsletter/confirm/{token}          — tasdiqlash
 *  GET  newsletter/unsubscribe/{token}      — chiqishni so'rash sahifasi (havolani tekshiruvchi
 *                                             skanerlar tasodifan chiqarib yubormasligi uchun)
 *  POST newsletter/unsubscribe/{token}      — chiqish (sahifadagi tugma)
 *  POST newsletter/unsubscribe/{token}/one-click — pochta dasturlaridagi "Obunadan chiqish"
 *                                             tugmasi (RFC 8058; CSRF'siz)
 */
class NewsletterSubscriptionController extends Controller
{
    public function __construct(
        private readonly NewsletterService $newsletter,
    ) {}

    public function store(SubscribeNewsletterRequest $request): RedirectResponse
    {
        $this->newsletter->subscribe(
            email: $request->string('email')->toString(),
            locale: app()->getLocale(),
            ip: $request->ip(),
        );

        // Manzil bazada bormi-yo'qmi — javobdan bilinmaydi
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Deyarli tayyor! Obunani tasdiqlash uchun elektron pochtangizga xat yubordik.'),
        ]);

        return back();
    }

    public function confirm(string $token): Response
    {
        return $this->page($this->newsletter->confirm($token) === 'invalid'
            ? 'invalid'
            : 'confirmed');
    }

    public function showUnsubscribe(string $token): Response
    {
        $subscriber = $this->newsletter->find($token);

        return match (true) {
            $subscriber === null => $this->page('invalid'),
            $subscriber->unsubscribed_at !== null => $this->page('unsubscribed'),
            default => $this->page('ask', $token, $subscriber->email),
        };
    }

    public function unsubscribe(string $token): Response
    {
        return $this->page($this->newsletter->unsubscribe($token) === 'invalid'
            ? 'invalid'
            : 'unsubscribed');
    }

    public function oneClick(string $token): HttpResponse
    {
        $this->newsletter->unsubscribe($token);

        return response()->noContent();
    }

    /**
     * @param  'confirmed'|'ask'|'unsubscribed'|'invalid'  $state
     */
    private function page(string $state, ?string $token = null, ?string $email = null): Response
    {
        app(SeoMeta::class)->noindex();

        return Inertia::render('web/newsletter/Status', [
            'state' => $state,
            'token' => $token,
            'email' => $email !== null ? $this->mask($email) : null,
        ]);
    }

    /** "reader@example.com" → "r•••r@example.com" */
    private function mask(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_strlen($local) <= 2
            ? mb_substr($local, 0, 1).'•••'
            : mb_substr($local, 0, 1).'•••'.mb_substr($local, -1);

        return $visible.'@'.$domain;
    }
}
