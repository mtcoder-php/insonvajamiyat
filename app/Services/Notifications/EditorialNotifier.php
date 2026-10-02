<?php

namespace App\Services\Notifications;

use App\Enums\ArticleStatus;
use App\Enums\RoleName;
use App\Events\ArticleStatusChanged;
use App\Models\Article;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ArticleUpdateNotification;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Collection;

/**
 * Tahririyat xodimlariga bildirishnomalar (admin header'dagi qo'ng'iroqcha + email):
 *   - yangi maqola tahririyat navbatiga tushdi → muharrirlar, bosh muharrir, super admin;
 *   - taqriz taklifi → taqrizchiga ("Taqrizlarim" sahifasiga havola);
 *   - taqrizchi qabul qildi / rad etdi / xulosa topshirdi → tayinlagan muharrirga.
 *
 * Amalni bajargan foydalanuvchining o'ziga xabar yuborilmaydi.
 * Ro'yxatdan o'tkazish: AppServiceProvider::configureEvents() → Event::subscribe().
 */
class EditorialNotifier
{
    /** Yangi maqola haqida xabar oladigan rollar */
    public const EDITORIAL_ROLES = [RoleName::SuperAdmin, RoleName::ChiefEditor, RoleName::Editor];

    public function onStatusChanged(ArticleStatusChanged $event): void
    {
        // Muallif yuborgan (bepul) yoki to'lovi tasdiqlangan maqola navbatga tushdi
        $fromQueue = $event->from === null || in_array($event->from, [ArticleStatus::Draft, ArticleStatus::AwaitingPayment], true);

        if ($event->to !== ArticleStatus::Submitted || ! $fromQueue) {
            return;
        }

        $this->send(
            $this->editors()->reject(fn (User $user): bool => $user->id === $event->actor?->id),
            new ArticleUpdateNotification(
                $event->article,
                ArticleUpdateNotification::SUBMITTED,
                __('Tahririyatga yangi maqola keldi'),
                $event->article->submitter->name,
                toStaff: true,
            ),
        );
    }

    /**
     * @param  Collection<int, Review>  $reviews
     */
    public function reviewersInvited(Article $article, Collection $reviews): void
    {
        foreach ($reviews as $review) {
            $due = $review->due_at !== null ? __('Javob muddati: :date', ['date' => $review->due_at->format('d.m.Y')]) : null;

            $review->reviewer->notify(new ArticleUpdateNotification(
                $article,
                ArticleUpdateNotification::REVIEW,
                __('Sizga yangi taqriz taklifi keldi'),
                is_string($due) ? $due : null,
                toStaff: true,
                link: route('admin.reviews.show', $review->id),
            ));
        }
    }

    /**
     * Taqrizchi javobi: accepted | declined | completed.
     */
    public function reviewerResponded(Review $review, string $action, ?string $comment = null): void
    {
        $headline = match ($action) {
            'accepted' => __('Taqrizchi taklifni qabul qildi'),
            'declined' => __('Taqrizchi taklifni rad etdi'),
            default => __('Taqriz topshirildi'),
        };

        $recipients = [];

        foreach ([$review->assigner, $review->article->handlingEditor] as $user) {
            if ($user instanceof User && $user->id !== $review->reviewer_id) {
                $recipients[$user->id] = $user;
            }
        }

        $this->send($recipients, new ArticleUpdateNotification(
            $review->article,
            ArticleUpdateNotification::REVIEW,
            $headline,
            $comment,
            toStaff: true,
        ));
    }

    /**
     * @return Collection<int, User>
     */
    private function editors(): Collection
    {
        return User::query()
            ->where('is_blocked', false)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', array_map(fn (RoleName $r): string => $r->value, self::EDITORIAL_ROLES)))
            ->get()
            ->toBase();
    }

    /**
     * @param  iterable<User>  $users
     */
    private function send(iterable $users, ArticleUpdateNotification $notification): void
    {
        foreach ($users as $user) {
            $user->notify($notification);
        }
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [ArticleStatusChanged::class => 'onStatusChanged'];
    }
}
