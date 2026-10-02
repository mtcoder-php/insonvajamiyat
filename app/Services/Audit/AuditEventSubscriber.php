<?php

namespace App\Services\Audit;

use App\Enums\AuditEvent;
use App\Events\ArticleStatusChanged;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;

/**
 * Tizim hodisalarini audit log'ga yozadi: kirish/chiqish, muvaffaqiyatsiz urinishlar,
 * parol tiklash va maqola holatining har bir o'zgarishi.
 *
 * Ataylab app/Listeners'dan tashqarida va metodlar "on*" deb nomlangan —
 * Laravel event discovery ularni ikkinchi marta ro'yxatdan o'tkazmasligi uchun.
 * Ro'yxatdan o'tkazish: AppServiceProvider::configureEvents() → Event::subscribe().
 */
class AuditEventSubscriber
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function onLogin(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->audit->log(AuditEvent::Login, $event->user, ['remember' => $event->remember], actor: $event->user);
        }
    }

    public function onLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->audit->log(AuditEvent::Logout, $event->user, actor: $event->user);
        }
    }

    public function onFailed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;

        $this->audit->log(
            AuditEvent::LoginFailed,
            $event->user instanceof User ? $event->user : null,
            ['email' => is_string($email) ? mb_strtolower($email) : null],
            actor: null,
        );
    }

    public function onLockout(Lockout $event): void
    {
        $email = $event->request->input('email');

        $this->audit->log(AuditEvent::Lockout, properties: ['email' => is_string($email) ? mb_strtolower($email) : null]);
    }

    public function onPasswordReset(PasswordReset $event): void
    {
        if ($event->user instanceof User) {
            $this->audit->log(AuditEvent::PasswordReset, $event->user, actor: $event->user);
        }
    }

    public function onArticleStatusChanged(ArticleStatusChanged $event): void
    {
        $this->audit->log(
            AuditEvent::ArticleStatusChanged,
            $event->article,
            [
                'from' => $event->from?->value,
                'to' => $event->to->value,
                'comment' => $event->comment,
            ],
            ($event->from?->label() ?? '—').' → '.$event->to->label(),
            $event->actor,
        );
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'onLogin',
            Logout::class => 'onLogout',
            Failed::class => 'onFailed',
            Lockout::class => 'onLockout',
            PasswordReset::class => 'onPasswordReset',
            ArticleStatusChanged::class => 'onArticleStatusChanged',
        ];
    }
}
