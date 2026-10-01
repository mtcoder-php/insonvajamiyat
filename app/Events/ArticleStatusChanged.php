<?php

namespace App\Events;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Maqola holati o'zgardi (ArticleWorkflow::transition).
 * Bildirishnoma va email listener'lari keyingi bosqichlarda ulanadi.
 */
class ArticleStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Article $article,
        public readonly ?ArticleStatus $from,
        public readonly ArticleStatus $to,
        public readonly ?User $actor,
        public readonly ?string $comment = null,
    ) {}
}
