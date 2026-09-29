<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Footer'dagi "Yangiliklardan xabardor bo'ling" obunachilari.
 *
 * @property int $id
 * @property string $email
 * @property string $locale
 * @property string $token
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
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->unsubscribed_at === null;
    }

    /**
     * @param  Builder<NewsletterSubscriber>  $query
     * @return Builder<NewsletterSubscriber>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('unsubscribed_at');
    }
}
