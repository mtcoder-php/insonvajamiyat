<?php

namespace App\Services\Settings;

use App\Enums\PaymentProvider;
use App\Services\Ai\AiSettings;
use App\Services\Payments\OnlinePaymentService;
use App\Support\Pdf\Qpdf;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * "Tizim holati" tabi: server muhiti va xizmatlar sozlanganligi (faqat o'qish).
 * Holat: ok — hammasi joyida, warning — e'tibor bering, error — ishlamaydi, off — o'chirilgan.
 */
class SystemStatus
{
    public function __construct(
        private readonly OnlinePaymentService $payments,
        private readonly AiSettings $ai,
    ) {}

    /**
     * @return array<int, array{key: string, label: string, value: string, state: string, hint: string|null}>
     */
    public function overview(): array
    {
        $production = app()->environment('production');
        $debug = (bool) config('app.debug');
        $queue = self::str(config('queue.default'));
        $mailer = self::str(config('mail.default'));
        $appUrl = self::str(config('app.url'));

        try {
            DB::select('select 1');
            $database = true;
        } catch (Throwable) {
            $database = false;
        }

        $qpdf = Qpdf::version();

        return [
            self::row('php', 'PHP', PHP_VERSION, 'ok'),
            self::row('laravel', 'Laravel', Application::VERSION, 'ok'),
            self::row('env', 'Muhit (APP_ENV)', self::str(config('app.env')), 'ok'),
            self::row('debug', 'Debug rejimi (APP_DEBUG)', $debug ? self::t('yoqilgan') : self::t("o'chirilgan"),
                $debug && $production ? 'error' : ($debug ? 'warning' : 'ok'),
                $debug && $production ? "Ishchi serverda APP_DEBUG=false bo'lishi shart — xatolar tafsiloti foydalanuvchiga ko'rinadi." : null),
            self::row('url', 'Sayt manzili (APP_URL)', $appUrl,
                str_starts_with($appUrl, 'https://') || ! $production ? 'ok' : 'warning',
                str_starts_with($appUrl, 'https://') || ! $production ? null : 'HTTPS tavsiya etiladi (to\'lov tizimlari talab qiladi).'),
            self::row('database', "Ma'lumotlar bazasi", self::str(config('database.default')), $database ? 'ok' : 'error'),
            self::row('queue', 'Navbat (QUEUE_CONNECTION)', $queue, $queue === 'sync' ? 'warning' : 'ok',
                $queue === 'sync' ? "AI so'rovlari va son PDF yig'ish so'rov ichida bajariladi — database/redis navbat va `php artisan queue:work` tavsiya etiladi." : 'Worker: php artisan queue:work --timeout=900'),
            self::row('mail', 'Pochta', $mailer === 'smtp' ? 'SMTP · '.self::str(config('mail.mailers.smtp.host')) : $mailer,
                $mailer === 'log' || $mailer === 'array' ? 'warning' : 'ok',
                $mailer === 'log' || $mailer === 'array' ? 'Xatlar yuborilmaydi, faqat logga yoziladi — «Pochta (SMTP)» tabida sozlang.' : null),
            self::row('storage', 'Fayllar havolasi (storage:link)', is_link(public_path('storage')) || is_dir(public_path('storage')) ? self::t('mavjud') : self::t("yo'q"),
                is_link(public_path('storage')) || is_dir(public_path('storage')) ? 'ok' : 'error',
                is_link(public_path('storage')) || is_dir(public_path('storage')) ? null : 'php artisan storage:link'),
            self::row('qpdf', "qpdf (son PDF yig'ish)", $qpdf ?? self::t("o'rnatilmagan"), Qpdf::available() ? 'ok' : 'warning',
                Qpdf::available() ? null : 'sudo apt install qpdf (11+ versiya)'),
            self::row('click', 'Click', $this->paymentValue(PaymentProvider::Click, 'payments.click.enabled'),
                $this->payments->isEnabled(PaymentProvider::Click) ? 'ok' : 'off',
                $this->payments->isEnabled(PaymentProvider::Click) ? null : '.env: CLICK_ENABLED, CLICK_SERVICE_ID, CLICK_MERCHANT_ID, CLICK_SECRET_KEY'),
            self::row('payme', 'Payme', $this->paymentValue(PaymentProvider::Payme, 'payments.payme.enabled'),
                $this->payments->isEnabled(PaymentProvider::Payme) ? 'ok' : 'off',
                $this->payments->isEnabled(PaymentProvider::Payme) ? null : '.env: PAYME_ENABLED, PAYME_MERCHANT_ID, PAYME_KEY'),
            self::row('ai', 'AI Studio', $this->ai->ready() ? self::str($this->ai->model()) : self::t('sozlanmagan'),
                $this->ai->ready() ? 'ok' : 'off',
                $this->ai->ready() ? null : 'AI Studio → Sozlamalar: API kalit va model'),
        ];
    }

    private function paymentValue(PaymentProvider $provider, string $flag): string
    {
        if ($this->payments->isEnabled($provider)) {
            return self::t('ulangan');
        }

        return config($flag) ? self::t("kalitlar to'liq emas") : self::t("o'chirilgan");
    }

    /**
     * @return array{key: string, label: string, value: string, state: string, hint: string|null}
     */
    private static function row(string $key, string $label, string $value, string $state, ?string $hint = null): array
    {
        // Yorliq va izoh — o'zbekcha kalit, joriy tilga tarjima qilinadi
        return ['key' => $key, 'label' => self::t($label), 'value' => $value, 'state' => $state, 'hint' => $hint !== null ? self::t($hint) : null];
    }

    private static function str(mixed $value): string
    {
        return is_string($value) ? $value : (is_scalar($value) ? (string) $value : '');
    }

    /**
     * @param  array<string, string|int>  $replace
     */
    private static function t(string $key, array $replace = []): string
    {
        $text = __($key, $replace);

        return is_string($text) ? $text : $key;
    }
}
