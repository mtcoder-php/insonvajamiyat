<?php

namespace App\Providers;

use App\Enums\RoleName;
use App\Models\User;
use App\Services\Audit\AuditEventSubscriber;
use App\Services\Notifications\EditorialNotifier;
use App\Services\Settings\SettingsStore;
use App\Services\Settings\SystemSettings;
use App\Support\Seo\SeoMeta;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Translatable\Facades\Translatable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Sozlamalar so'rov davomida bir marta o'qiladi (queue worker'da har job uchun yangilanadi)
        $this->app->scoped(SettingsStore::class);
        // Server tomonidagi SEO teglari (app.blade.php) — har so'rov uchun alohida
        $this->app->scoped(SeoMeta::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        // Admin → Tizim sozlamalari: jurnal rekvizitlari va SMTP (settings jadvali → config)
        SystemSettings::apply();
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

        // Audit log: kirish/chiqish, muvaffaqiyatsiz urinishlar, maqola holatlari
        Event::subscribe(AuditEventSubscriber::class);

        // Tahririyat xodimlariga bildirishnomalar (yangi maqola navbatga tushdi)
        Event::subscribe(EditorialNotifier::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Tarjimali maydon joriy tilda bo'lmasa — fallback_locale, u ham bo'lmasa mavjud istalgan til
        // (masalan, faqat ruscha sarlavhali maqola o'zbekcha interfeysda bo'sh ko'rinmasligi uchun)
        Translatable::fallback(fallbackAny: true);

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
