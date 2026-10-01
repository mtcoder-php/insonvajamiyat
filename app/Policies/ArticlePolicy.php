<?php

namespace App\Policies;

use App\Enums\ArticleStatus;
use App\Enums\PermissionName;
use App\Models\Article;
use App\Models\User;

/**
 * Maqola ruxsatlari.
 *
 *   Muallif — o'z maqolalarini (yuboruvchi yoki hammuallif sifatida) ko'radi,
 *             qoralama / tuzatish bosqichida tahrirlaydi, ruxsat etilgan holatda qaytarib oladi.
 *   Xodimlar — articles.view_any ruxsati bilan barcha maqolalarni ko'radi
 *             (muharrir/taqrizchi amallari keyingi bosqichlarda qo'shiladi).
 * Super Admin — Gate::before orqali hammasiga ega.
 */
class ArticlePolicy
{
    public function view(User $user, Article $article): bool
    {
        return $this->isAuthor($user, $article)
            || $user->can(PermissionName::ArticlesViewAny->value);
    }

    /** Muallif maqolani tahrirlay oladimi (qoralama yoki tuzatish talab etilganda) */
    public function update(User $user, Article $article): bool
    {
        return $article->submitter_id === $user->id
            && in_array($article->status, [ArticleStatus::Draft, ArticleStatus::RevisionRequired], true);
    }

    /** Yangi maqola formasini davom ettirish (faqat yuboruvchi, faqat qoralama) */
    public function editDraft(User $user, Article $article): bool
    {
        return $article->submitter_id === $user->id && $article->status === ArticleStatus::Draft;
    }

    /** Muallif maqolani qaytarib olishi (state machine ruxsat bergan holatlarda) */
    public function withdraw(User $user, Article $article): bool
    {
        return $article->submitter_id === $user->id
            && $article->status->canTransitionTo(ArticleStatus::Withdrawn);
    }

    /** Qoralamani butunlay o'chirish */
    public function delete(User $user, Article $article): bool
    {
        return $article->submitter_id === $user->id && $article->status === ArticleStatus::Draft;
    }

    public function downloadFiles(User $user, Article $article): bool
    {
        return $this->view($user, $article);
    }

    private function isAuthor(User $user, Article $article): bool
    {
        if ($article->submitter_id === $user->id) {
            return true;
        }

        return $article->authors()->where('user_id', $user->id)->exists();
    }
}
