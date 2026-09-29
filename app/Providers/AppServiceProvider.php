<?php

namespace App\Providers;

use App\Enums\RoleName;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureEvents();
    }

    /**
     * Super Admin barcha ruxsat tekshiruvlaridan o'tadi (TZ 3.2: "tizimning barcha qismlariga to'liq kirish").
     * Faqat `true` qaytaradi — boshqa foydalanuvchilar uchun oddiy Policy/permission ishlaydi.
     */
    protected function configureAuthorization(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->hasRole(RoleName::SuperAdmin) ? true : null;
        });
    }

    /**
     * Audit uchun oxirgi kirish vaqti va IP manzili.
     */
    protected function configureEvents(): void
    {
        Event::listen(function (Login $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            $event->user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => request()->ip(),
            ])->saveQuietly();
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
