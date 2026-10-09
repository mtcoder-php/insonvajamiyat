<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Footer'dagi "Yangiliklardan xabardor bo'ling" obunachilari.
 *
 * Holatlar:
 *   kutilmoqda   — confirmed_at bo'sh (tasdiqlash xati yuborilgan, havola bosilmagan)
 *   faol         — confirmed_at bor, unsubscribed_at bo'sh (faqat ularga xat yuboriladi)
 *   chiqib ketgan — unsubscribed_at bor
 * `token` — tasdiqlash va obunadan chiqish havolalaridagi maxfiy kalit.
 * 30 kundan beri tasdiqlanmagan yozuvlar har kuni o'chiriladi (model:prune).
 *
 * @property int $id
 * @property string $email
 * @property string $locale
 * @property string $token
 * @property Carbon|null $confirmation_sent_at
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $unsubscribed_at
 * @property string|null $ip
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['email', 'locale', 'token', 'ip'])]
#[Hidden(['token', 'ip'])]
class NewsletterSubscriber extends Model
{
    use MassPrunable;

    /** Tasdiqlanmagan obuna shuncha kundan keyin o'chiriladi */
    public const UNCONFIRMED_TTL_DAYS = 30;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confirmation_sent_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->unsubscribed_at === null;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null && $this->unsubscribed_at === null;
    }

    /**
     * Obunadan chiqmaganlar (tasdiqlangan yoki kutilayotgan).
     *
     * @param  Builder<NewsletterSubscriber>  $query
     * @return Builder<NewsletterSubscriber>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('unsubscribed_at');
    }

    /**
     * Xat yuboriladiganlar: tasdiqlangan va obunadan chiqmagan.
     *
     * @param  Builder<NewsletterSubscriber>  $query
     * @return Builder<NewsletterSubscriber>
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }

    public function confirmUrl(): string
    {
        return route('newsletter.confirm', ['token' => $this->token]);
    }

    public function unsubscribeUrl(): string
    {
        return route('newsletter.unsubscribe', ['token' => $this->token]);
    }

    /**
     * Bir marta bosib chiqish (RFC 8058, List-Unsubscribe-Post) manzili.
     */
    public function oneClickUnsubscribeUrl(): string
    {
        return route('newsletter.unsubscribe.one-click', ['token' => $this->token]);
    }

    /**
     * @return Builder<NewsletterSubscriber>
     */
    public function prunable(): Builder
    {
        return NewsletterSubscriber::query()
            ->whereNull('confirmed_at')
            ->where('created_at', '<', now()->subDays(self::UNCONFIRMED_TTL_DAYS));
    }
}
