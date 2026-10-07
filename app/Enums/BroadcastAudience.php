<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/** broadcasts.audience — ommaviy xabar kimga yuboriladi (faqat faol, bloklanmagan foydalanuvchilar) */
enum BroadcastAudience: string
{
    use EnumHelpers;

    case Authors = 'authors';                 // Barcha mualliflar
    case ActiveAuthors = 'active_authors';    // Maqola yuborgan mualliflar
    case PublishedAuthors = 'published_authors'; // Maqolasi nashr etilgan mualliflar
    case Reviewers = 'reviewers';             // Taqrizchilar
    case Staff = 'staff';                     // Tahririyat xodimlari
    case All = 'all';                         // Barcha foydalanuvchilar

    public function label(): string
    {
        return match ($this) {
            self::Authors => __('Barcha mualliflar'),
            self::ActiveAuthors => __('Maqola yuborgan mualliflar'),
            self::PublishedAuthors => __('Maqolasi nashr etilgan mualliflar'),
            self::Reviewers => __('Taqrizchilar'),
            self::Staff => __('Tahririyat xodimlari'),
            self::All => __('Barcha foydalanuvchilar'),
        };
    }

    /**
     * @return Builder<User>
     */
    public function query(): Builder
    {
        $query = User::query()->active();

        return match ($this) {
            self::Authors => $query->role(RoleName::Author->value),
            self::ActiveAuthors => $query->role(RoleName::Author->value)
                ->whereHas('submittedArticles', fn ($q) => $q->where('status', '!=', ArticleStatus::Draft->value)),
            self::PublishedAuthors => $query->role(RoleName::Author->value)
                ->whereHas('submittedArticles', fn ($q) => $q->where('status', ArticleStatus::Published->value)),
            self::Reviewers => $query->role(RoleName::Reviewer->value),
            self::Staff => $query->staff(),
            self::All => $query,
        };
    }
}
