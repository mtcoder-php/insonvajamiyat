<?php

namespace App\Services\Payments;

use App\Enums\ArticlePaymentStatus;
use App\Enums\ArticleStatus;
use App\Enums\AuditEvent;
use App\Models\Article;
use App\Models\User;
use App\Notifications\ArticleUpdateNotification;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * To'lov eslatmalari (TZ 4.2.8): "To'lov kutilmoqda" holatidagi maqola muallifiga
 * kabinet bildirishnomasi + email.
 *
 *  - Avtomatik: yuborilganidan journal.payment_reminders.days kunlarda (masalan 3, 7, 14),
 *    har biri bir martadan (app:payment-reminders, har kuni).
 *  - Qo'lda: Admin → To'lovlar → "Eslatma yuborish" (bitta yoki hammasiga).
 *  - Ikki eslatma orasida kamida cooldown_hours soat — muallif xatlarga ko'milib ketmaydi.
 */
class PaymentReminderService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Avtomatik jadval bo'yicha vaqti kelgan eslatmalarni yuboradi.
     *
     * @return int yuborilgan eslatmalar soni
     */
    public function sendDue(): int
    {
        $days = self::days();

        if ($days === []) {
            return 0;
        }

        $sent = 0;

        $this->awaiting()
            ->where('payment_reminders_count', '<', count($days))
            ->whereNotNull('submitted_at')
            ->with(['submitter', 'articleType'])
            ->chunkById(100, function (Collection $articles) use ($days, &$sent): void {
                foreach ($articles as $article) {
                    /** @var Article $article */
                    $threshold = $days[$article->payment_reminders_count] ?? null;

                    if ($threshold === null || $article->submitted_at === null
                        || $article->submitted_at->diffInDays(now(), true) < $threshold) {
                        continue;
                    }

                    if ($this->send($article, null)) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    /**
     * Tahririyat tomonidan bitta maqola bo'yicha eslatma.
     * false — maqola endi to'lov kutmayapti yoki yaqinda eslatma yuborilgan.
     */
    public function remind(Article $article, User $actor): bool
    {
        if (! self::isAwaiting($article)) {
            return false;
        }

        $article->loadMissing(['submitter', 'articleType']);

        return $this->send($article, $actor);
    }

    /**
     * Tahririyat tomonidan barcha to'lov kutilayotgan maqolalar bo'yicha (cooldown hisobga olinadi).
     *
     * @return int yuborilgan eslatmalar soni
     */
    public function remindAll(User $actor): int
    {
        $sent = 0;

        $this->awaiting()
            ->with(['submitter', 'articleType'])
            ->chunkById(100, function (Collection $articles) use ($actor, &$sent): void {
                foreach ($articles as $article) {
                    /** @var Article $article */
                    if ($this->send($article, $actor)) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    /** Keyingi eslatmani qachondan yuborish mumkin (null — hozir mumkin) */
    public static function availableAt(Article $article): ?CarbonInterface
    {
        if ($article->payment_reminded_at === null) {
            return null;
        }

        $next = $article->payment_reminded_at->toImmutable()->addHours(self::cooldownHours());

        return $next->isFuture() ? $next : null;
    }

    public static function isAwaiting(Article $article): bool
    {
        return $article->status === ArticleStatus::AwaitingPayment
            && ! $article->payment_status->isSettled()
            && $article->payment_status !== ArticlePaymentStatus::Refunded;
    }

    private function send(Article $article, ?User $actor): bool
    {
        if (self::availableAt($article) !== null) {
            return false;
        }

        $submitter = $article->submitter;
        $amount = number_format((float) $article->articleType->price, 0, '.', ' ');
        $type = $article->articleType->name;

        $submitter->notifyInLocale(fn (): ArticleUpdateNotification => new ArticleUpdateNotification(
            $article,
            ArticleUpdateNotification::PAYMENT_REMINDER,
            __("Maqolangiz to'lovni kutmoqda"),
            self::text("Maqola turi: :type. Nashr to'lovi: :amount so'm. To'lovni kabinetdagi maqola sahifasida Click, Payme yoki bank orqali amalga oshirishingiz mumkin — shundan so'ng maqola tahririyat ko'rib chiqishiga o'tadi.", [
                'type' => $type,
                'amount' => $amount,
            ]),
            link: route('cabinet.articles.show', $article->uuid).'#payment',
        ));

        $values = [
            'payment_reminders_count' => min(255, $article->payment_reminders_count + 1),
            'payment_reminded_at' => now(),
        ];
        // updated_at o'zgarmaydi: eslatma maqolaning o'zini tahrirlash emas
        Article::query()->whereKey($article->id)->toBase()->update($values);
        $article->forceFill($values)->syncOriginalAttributes(array_keys($values));

        $this->audit->log(AuditEvent::PaymentReminded, $article, [
            'count' => $article->payment_reminders_count,
            'automatic' => $actor === null,
        ], actor: $actor);

        return true;
    }

    /**
     * @return Builder<Article>
     */
    private function awaiting(): Builder
    {
        return Article::query()
            ->where('status', ArticleStatus::AwaitingPayment->value)
            ->whereIn('payment_status', [ArticlePaymentStatus::Unpaid->value, ArticlePaymentStatus::Pending->value]);
    }

    /**
     * Avtomatik eslatma kunlari (o'sish tartibida, takrorlarsiz).
     *
     * @return list<int>
     */
    public static function days(): array
    {
        $days = config('journal.payment_reminders.days', []);

        if (! is_array($days)) {
            return [];
        }

        $days = array_values(array_unique(array_filter(array_map(
            fn (mixed $d): int => is_numeric($d) ? (int) $d : 0,
            $days,
        ), fn (int $d): bool => $d > 0)));
        sort($days);

        return $days;
    }

    private static function cooldownHours(): int
    {
        $hours = config('journal.payment_reminders.cooldown_hours', 24);

        return is_numeric($hours) ? max(0, (int) $hours) : 24;
    }

    /**
     * @param  array<string, string>  $replace
     */
    private static function text(string $key, array $replace): string
    {
        $text = __($key, $replace);

        return is_string($text) ? $text : $key;
    }
}
