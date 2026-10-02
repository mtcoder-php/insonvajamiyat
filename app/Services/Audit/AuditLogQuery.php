<?php

namespace App\Services\Audit;

use App\Enums\AuditEvent;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\JournalIssue;
use App\Models\User;
use App\Support\MediaUrl;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * Admin → Audit log: filtrlar, ro'yxat va qisqa statistika.
 */
class AuditLogQuery
{
    public const PER_PAGE = 25;

    public const SEVERITIES = ['info', 'warning', 'danger'];

    /**
     * @param  array<string, mixed>  $input  so'rov parametrlari
     * @return array{q: string|null, category: string|null, event: string|null, severity: string|null, user: int|null, from: string|null, to: string|null}
     */
    public static function filters(array $input): array
    {
        $string = fn (string $key, int $max = 100): ?string => is_string($input[$key] ?? null) && trim($input[$key]) !== ''
            ? mb_substr(trim($input[$key]), 0, $max)
            : null;

        $category = $string('category');
        $event = $string('event');
        $severity = $string('severity');
        $user = $input['user'] ?? null;

        return [
            'q' => $string('q'),
            'category' => $category !== null && array_key_exists($category, AuditEvent::categories()) ? $category : null,
            'event' => $event !== null && AuditEvent::tryFrom($event) !== null ? $event : null,
            'severity' => in_array($severity, self::SEVERITIES, true) ? $severity : null,
            'user' => is_numeric($user) ? (int) $user : null,
            'from' => self::date($string('from', 10)),
            'to' => self::date($string('to', 10)),
        ];
    }

    /**
     * @param  array{q: string|null, category: string|null, event: string|null, severity: string|null, user: int|null, from: string|null, to: string|null}  $filters
     * @return LengthAwarePaginator<int, AuditLog>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->query($filters)
            ->with(['user.roles', 'user.authorProfile'])
            ->latest('created_at')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @param  array{q: string|null, category: string|null, event: string|null, severity: string|null, user: int|null, from: string|null, to: string|null}  $filters
     * @return Builder<AuditLog>
     */
    public function query(array $filters): Builder
    {
        $events = null;

        if ($filters['event'] !== null) {
            $events = [$filters['event']];
        } elseif ($filters['category'] !== null) {
            $events = array_map(fn (AuditEvent $e): string => $e->value, AuditEvent::inCategory($filters['category']));
        }

        if ($filters['severity'] !== null) {
            $bySeverity = array_map(
                fn (AuditEvent $e): string => $e->value,
                array_filter(AuditEvent::cases(), fn (AuditEvent $e): bool => $e->severity() === $filters['severity']),
            );
            $events = $events === null ? $bySeverity : array_values(array_intersect($events, $bySeverity));
        }

        return AuditLog::query()
            ->when($events !== null, fn (Builder $q) => $q->whereIn('event', $events ?? []))
            ->when($filters['user'] !== null, fn (Builder $q) => $q->where('user_id', $filters['user']))
            ->when($filters['from'] !== null, fn (Builder $q) => $q->where('created_at', '>=', CarbonImmutable::parse((string) $filters['from'])->startOfDay()))
            ->when($filters['to'] !== null, fn (Builder $q) => $q->where('created_at', '<=', CarbonImmutable::parse((string) $filters['to'])->endOfDay()))
            ->when($filters['q'] !== null, function (Builder $q) use ($filters): void {
                $term = '%'.$filters['q'].'%';

                $q->where(fn (Builder $w) => $w
                    ->where('subject_label', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('ip_address', 'like', $term)
                    ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term)));
            });
    }

    /**
     * Obyekt havolalari bir so'rovda: "article:5" => admin URL.
     *
     * @param  iterable<int, AuditLog>  $logs
     * @return array<string, string>
     */
    public static function links(iterable $logs): array
    {
        $ids = ['article' => [], 'issue' => [], 'user' => []];

        foreach ($logs as $log) {
            if ($log->subject_id !== null && array_key_exists((string) $log->subject_type, $ids)) {
                $ids[(string) $log->subject_type][] = $log->subject_id;
            }
        }

        $links = [];

        foreach (Article::query()->withTrashed()->whereIn('id', $ids['article'])->pluck('uuid', 'id') as $id => $uuid) {
            $links['article:'.$id] = route('admin.articles.index', ['queue' => 'all', 'article' => $uuid]);
        }

        foreach (JournalIssue::query()->whereIn('id', $ids['issue'])->pluck('slug', 'id') as $id => $slug) {
            $links['issue:'.$id] = route('admin.issues.show', $slug);
        }

        foreach (array_unique($ids['user']) as $id) {
            $links['user:'.$id] = route('admin.users.show', $id);
        }

        return $links;
    }

    /**
     * @param  array<string, string>  $links  self::links()
     * @return array<string, mixed>
     */
    public static function row(AuditLog $log, array $links = []): array
    {
        $user = $log->user;
        $role = $user?->roles->pluck('name')->first();

        return [
            'id' => $log->id,
            'event' => $log->event->value,
            'label' => $log->event->label(),
            'category' => $log->event->category(),
            'severity' => $log->event->severity(),
            'description' => $log->description,
            'subject' => $log->subject_type !== null ? [
                'type' => $log->subject_type,
                'id' => $log->subject_id,
                'label' => $log->subject_label,
                'url' => $links[$log->subject_type.':'.$log->subject_id] ?? null,
            ] : null,
            'user' => $user !== null ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => is_string($role) ? RoleName::tryFrom($role)?->label() : null,
                'avatarUrl' => MediaUrl::from($user->authorProfile?->avatar_path),
                'deleted' => $user->trashed(),
            ] : null,
            'properties' => $log->properties,
            'ip' => $log->ip_address,
            'userAgent' => $log->user_agent,
            'createdAt' => $log->created_at->toIso8601String(),
        ];
    }

    /**
     * Sarlavha ostidagi qisqa statistika: bugun, 24 soatdagi muvaffaqiyatsiz kirishlar, faol xodimlar.
     *
     * @return array{today: int, failedLogins: int, activeUsers: int, total: int}
     */
    public function stats(): array
    {
        $dayAgo = now()->subDay();

        return [
            'today' => AuditLog::query()->where('created_at', '>=', now()->startOfDay())->count(),
            'failedLogins' => AuditLog::query()
                ->whereIn('event', [AuditEvent::LoginFailed->value, AuditEvent::Lockout->value])
                ->where('created_at', '>=', $dayAgo)
                ->count(),
            'activeUsers' => AuditLog::query()
                ->where('created_at', '>=', $dayAgo)
                ->whereNotNull('user_id')
                ->distinct()
                ->count('user_id'),
            'total' => AuditLog::query()->count(),
        ];
    }

    /**
     * Filtr uchun: audit'da uchragan xodimlar (oxirgi 500 ta noyob).
     *
     * @return array<int, array{value: int, label: string}>
     */
    public function userOptions(): array
    {
        return User::query()
            ->withTrashed()
            ->whereIn('id', AuditLog::query()->whereNotNull('user_id')->select('user_id')->distinct())
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->map(fn (User $u): array => ['value' => $u->id, 'label' => $u->name])
            ->all();
    }

    private static function date(?string $value): ?string
    {
        if ($value === null || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value)?->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
