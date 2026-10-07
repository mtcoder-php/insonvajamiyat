<?php

namespace App\Services\People;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Subject;
use App\Models\User;
use App\Services\Editorial\EditorialWorkspace;

/**
 * Mualliflar va taqrizchilar sahifalari uchun umumiy ko'rinishlar:
 * profil kartasi, yo'nalishlar va maqola qatori.
 */
final class PersonPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function profile(User $user): array
    {
        $profile = $user->authorProfile;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatarUrl' => $user->avatarUrl(),
            'organization' => $profile?->organization,
            'department' => $profile?->department,
            'position' => $profile?->position,
            'degree' => $profile?->academic_degree,
            'title' => $profile?->academic_title,
            'orcid' => $profile?->orcid,
            'country' => $profile?->country,
            'city' => $profile?->city,
            'bio' => $profile?->bio,
            'isBlocked' => $user->is_blocked,
            'isVerified' => $user->email_verified_at !== null,
            'createdAt' => $user->created_at?->toIso8601String(),
            'lastLoginAt' => $user->last_login_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function subjects(User $user): array
    {
        return $user->subjects
            ->sortBy('sort_order')
            ->map(fn (Subject $s): array => ['id' => $s->id, 'name' => $s->name])
            ->values()
            ->all();
    }

    /**
     * Maqola qatori (admin uchun): tahririyat raqami, holat guruhi, havolalar.
     *
     * @return array<string, mixed>
     */
    public static function article(Article $article): array
    {
        return [
            'uuid' => $article->uuid,
            'code' => EditorialWorkspace::code($article),
            'title' => $article->title,
            'status' => $article->status->value,
            'statusLabel' => $article->status->label(),
            'statusGroup' => $article->status->group(),
            'subject' => $article->subject?->name,
            'submittedAt' => $article->submitted_at?->toIso8601String(),
            'publishedAt' => $article->published_at?->toIso8601String(),
            'views' => $article->views_count,
            'downloads' => $article->downloads_count,
            'adminUrl' => route('admin.articles.index', ['queue' => 'all', 'article' => $article->uuid]),
            'publicUrl' => $article->status === ArticleStatus::Published && $article->slug !== null
                ? route('articles.show', $article->slug)
                : null,
        ];
    }
}
