<?php

namespace App\Services\Admin;

use App\Enums\AdminSection;
use App\Enums\ArticleStatus;
use App\Enums\IssueStatus;
use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Models\AiRequest;
use App\Models\Article;
use App\Models\JournalIssue;
use App\Models\Payment;
use App\Models\User;

/**
 * Admin sidebar'idagi raqamlar (badge'lar).
 *
 * Har biri "e'tibor talab qiladigan" yoki umumiy son:
 *   Maqolalar     — muharrir ko'rib chiqishini kutayotganlar
 *   Jurnallar     — shakllantirilayotgan (chop etilmagan) sonlar
 *   Mualliflar    — faol mualliflar
 *   Taqrizchilar  — faol taqrizchilar
 *   To'lovlar     — kutilayotgan / jarayondagi to'lovlar
 *   AI xizmatlari — bugungi so'rovlar
 *   Xabarlar      — o'qilmagan bildirishnomalar
 * Faqat foydalanuvchi ko'ra oladigan bo'limlar hisoblanadi; 0 bo'lsa badge chiqmaydi.
 */
class NavigationBadges
{
    /** Ko'rib chiqishni kutayotgan maqola holatlari */
    private const AWAITING_EDITOR = [
        ArticleStatus::Submitted,
        ArticleStatus::UnderReview,
        ArticleStatus::Resubmitted,
    ];

    /**
     * @return array<string, int>
     */
    public function for(User $user): array
    {
        $counters = [
            AdminSection::Articles->value => fn (): int => Article::query()
                ->whereIn('status', array_map(fn (ArticleStatus $s): string => $s->value, self::AWAITING_EDITOR))
                ->count(),
            AdminSection::Issues->value => fn (): int => JournalIssue::query()
                ->where('status', IssueStatus::Draft->value)
                ->count(),
            AdminSection::Authors->value => fn (): int => User::query()->active()->role(RoleName::Author->value)->count(),
            AdminSection::Reviewers->value => fn (): int => User::query()->active()->role(RoleName::Reviewer->value)->count(),
            AdminSection::Payments->value => fn (): int => Payment::query()
                ->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::Processing->value])
                ->count(),
            AdminSection::Ai->value => fn (): int => AiRequest::query()
                ->where('created_at', '>=', now()->startOfDay())
                ->count(),
            AdminSection::Messages->value => fn (): int => $user->unreadNotifications()->count(),
        ];

        $badges = [];

        foreach ($counters as $key => $count) {
            if (! $user->can(AdminSection::from($key)->permission()->value)) {
                continue;
            }

            $value = $count();

            if ($value > 0) {
                $badges[$key] = $value;
            }
        }

        return $badges;
    }
}
