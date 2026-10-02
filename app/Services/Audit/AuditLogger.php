<?php

namespace App\Services\Audit;

use App\Enums\AuditEvent;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\JournalIssue;
use App\Models\Payment;
use App\Models\Review;
use App\Models\User;
use App\Services\Editorial\EditorialWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

/**
 * Audit log'ga yozishning YAGONA yo'li.
 *
 *   app(AuditLogger::class)->log(AuditEvent::UserBlocked, $user, ['reason' => $reason]);
 *
 * - Amal bajaruvchi: $actor yoki joriy autentifikatsiya qilingan foydalanuvchi.
 * - IP va brauzer (user agent) joriy so'rovdan olinadi.
 * - Obyekt nomi (subject_label) nusxa sifatida saqlanadi.
 * - Audit yozuvidagi xato asosiy amalni to'xtatmaydi (report() bilan loglanadi).
 * - Maxfiy maydonlar (parol, token) properties'ga hech qachon tushmaydi.
 */
class AuditLogger
{
    /** @var array<class-string<Model>, string> */
    public const SUBJECT_TYPES = [
        Article::class => 'article',
        User::class => 'user',
        Payment::class => 'payment',
        JournalIssue::class => 'issue',
        Review::class => 'review',
    ];

    /** properties ichida saqlanmaydigan kalitlar */
    private const HIDDEN = ['password', 'password_confirmation', 'current_password', 'remember_token', 'token', 'two_factor_secret', 'two_factor_recovery_codes'];

    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(
        AuditEvent $event,
        ?Model $subject = null,
        array $properties = [],
        ?string $description = null,
        ?User $actor = null,
    ): ?AuditLog {
        try {
            $actor ??= $this->currentUser();
            $request = request();

            return AuditLog::query()->create([
                'user_id' => $actor?->id,
                'event' => $event,
                'subject_type' => $subject !== null ? (self::SUBJECT_TYPES[$subject::class] ?? Str::snake(class_basename($subject))) : null,
                'subject_id' => $subject?->getKey(),
                'subject_label' => $subject !== null ? Str::limit($this->labelFor($subject), 250) : null,
                'description' => $description !== null ? Str::limit($description, 495) : null,
                'properties' => $properties === [] ? null : $this->clean($properties),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Modelning o'zgargan maydonlari: ['old' => [...], 'new' => [...]] (faqat $fields ichidan).
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array{old: array<string, mixed>, new: array<string, mixed>}|array{}
     */
    public static function diff(array $before, array $after): array
    {
        $old = [];
        $new = [];

        foreach ($after as $key => $value) {
            $previous = $before[$key] ?? null;

            if ($previous != $value) {
                $old[$key] = $previous;
                $new[$key] = $value;
            }
        }

        return $new === [] ? [] : ['old' => $old, 'new' => $new];
    }

    public function labelFor(Model $subject): string
    {
        return match (true) {
            $subject instanceof Article => EditorialWorkspace::code($subject).' · '.Str::limit((string) $subject->title, 120),
            $subject instanceof User => trim($subject->name.' ('.$subject->email.')'),
            $subject instanceof Payment => (string) ($subject->receipt_number ?? 'To\'lov #'.$subject->id),
            $subject instanceof JournalIssue => $subject->label,
            $subject instanceof Review => 'Taqriz #'.$subject->id,
            default => class_basename($subject).' #'.(is_scalar($subject->getKey()) ? (string) $subject->getKey() : ''),
        };
    }

    private function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function clean(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array($key, self::HIDDEN, true)) {
                continue;
            }

            $clean[$key] = match (true) {
                is_array($value) => $this->clean($value),
                $value instanceof \BackedEnum => $value->value,
                $value instanceof \DateTimeInterface => $value->format(DATE_ATOM),
                is_string($value) => Str::limit($value, 500),
                default => $value,
            };
        }

        return $clean;
    }
}
