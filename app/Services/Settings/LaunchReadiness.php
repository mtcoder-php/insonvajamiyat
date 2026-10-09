<?php

namespace App\Services\Settings;

use App\Enums\JournalDocumentKind;
use App\Enums\PaymentProvider;
use App\Enums\RoleName;
use App\Models\ArticleType;
use App\Models\Backup;
use App\Models\EditorialBoardMember;
use App\Models\JournalDocument;
use App\Models\Page;
use App\Models\Subject;
use App\Models\User;
use App\Services\Ai\AiSettings;
use App\Services\Backup\BackupService;
use App\Services\Payments\OnlinePaymentService;
use App\Support\MediaUrl;
use App\Support\Pdf\Qpdf;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * "Ishga tushirishga tayyorlik" ro'yxati — admin → Tizim sozlamalari → Tizim holati
 * va `php artisan app:launch-check` uchun bir xil tekshiruvlar.
 *
 * Holat: ok — joyida, warning — tavsiya, error — ishga tushirishdan oldin tuzatish shart,
 * off — ixtiyoriy xizmat ulanmagan.
 */
class LaunchReadiness
{
    public const SCHEDULER_HEARTBEAT = 'heartbeat:scheduler';

    public const QUEUE_HEARTBEAT = 'heartbeat:queue';

    /** @var list<array{key: string, group: string, label: string, state: 'ok'|'warning'|'error'|'off', value: string, hint: string|null}> */
    private array $checks = [];

    public function __construct(
        private readonly OnlinePaymentService $payments,
        private readonly AiSettings $ai,
        private readonly BackupService $backups,
    ) {}

    /**
     * @return array{ready: bool, counts: array{ok: int, warning: int, error: int, off: int}, groups: list<array{key: string, label: string, checks: list<array{key: string, group: string, label: string, state: 'ok'|'warning'|'error'|'off', value: string, hint: string|null}>}>}
     */
    public function report(): array
    {
        $this->checks = [];
        $production = app()->environment('production');

        $this->server($production);
        $this->background($production);
        $this->journal();
        $this->content();
        $this->payment($production);
        $this->security($production);
        $this->backup();
        $this->integrations();

        $counts = ['ok' => 0, 'warning' => 0, 'error' => 0, 'off' => 0];

        foreach ($this->checks as $check) {
            $counts[$check['state']]++;
        }

        $groups = [];

        foreach (self::groups() as $key => $label) {
            $items = array_values(array_filter($this->checks, fn (array $c): bool => $c['group'] === $key));

            if ($items !== []) {
                $groups[] = ['key' => $key, 'label' => $label, 'checks' => $items];
            }
        }

        return ['ready' => $counts['error'] === 0, 'counts' => $counts, 'groups' => $groups];
    }

    /**
     * @return array<string, string>
     */
    public static function groups(): array
    {
        return [
            'server' => self::t('Server va muhit'),
            'background' => self::t('Fon jarayonlari'),
            'journal' => self::t('Jurnal rekvizitlari'),
            'content' => self::t('Sayt kontenti'),
            'payment' => self::t("To'lovlar"),
            'security' => self::t('Xavfsizlik'),
            'backup' => self::t('Zaxira nusxa'),
            'integrations' => self::t('Integratsiyalar'),
        ];
    }

    private function server(bool $production): void
    {
        $env = self::str(config('app.env'));
        $this->add('server', 'env', self::t('Muhit (APP_ENV)'), $production ? 'ok' : 'warning', $env,
            $production ? null : self::t('Ishchi serverda APP_ENV=production bo\'lishi kerak.'));

        $debug = (bool) config('app.debug');
        $this->add('server', 'debug', self::t('Debug rejimi'), $debug ? ($production ? 'error' : 'warning') : 'ok',
            $debug ? self::t('yoqilgan') : self::t("o'chirilgan"),
            $debug ? self::t('APP_DEBUG=false qiling — aks holda xatolar tafsiloti foydalanuvchilarga ko\'rinadi.') : null);

        $url = self::str(config('app.url'));
        $https = str_starts_with($url, 'https://');
        $this->add('server', 'https', self::t('HTTPS manzil (APP_URL)'), $https ? 'ok' : ($production ? 'error' : 'warning'), $url,
            $https ? null : self::t('APP_URL https:// bilan boshlanishi shart (to\'lov tizimlari va xavfsiz cookie uchun).'));

        $this->add('server', 'key', self::t('Shifrlash kaliti (APP_KEY)'), self::str(config('app.key')) !== '' ? 'ok' : 'error',
            self::str(config('app.key')) !== '' ? self::t("o'rnatilgan") : self::t("yo'q"),
            self::str(config('app.key')) !== '' ? null : 'php artisan key:generate');

        $pending = $this->pendingMigrations();
        $this->add('server', 'migrations', self::t('Migratsiyalar'), $pending === null ? 'warning' : ($pending === 0 ? 'ok' : 'error'),
            $pending === null ? self::t('tekshirib bo\'lmadi') : ($pending === 0 ? self::t('hammasi bajarilgan') : self::t(':count ta bajarilmagan', ['count' => (string) $pending])),
            $pending ? 'php artisan migrate --force' : null);

        $linked = is_link(public_path('storage')) || is_dir(public_path('storage'));
        $this->add('server', 'storage', self::t('Fayllar havolasi (storage:link)'), $linked ? 'ok' : 'error',
            $linked ? self::t('mavjud') : self::t("yo'q"), $linked ? null : 'php artisan storage:link');

        $this->add('server', 'qpdf', self::t("qpdf (son PDF'ini yig'ish)"), Qpdf::available() ? 'ok' : 'warning',
            Qpdf::version() ?? self::t("o'rnatilmagan"), Qpdf::available() ? null : 'sudo apt install qpdf');

        $mailer = self::str(config('mail.default'));
        $smtp = $mailer === 'smtp' && self::str(config('mail.mailers.smtp.host')) !== '';
        $this->add('server', 'mail', self::t('Pochta (SMTP)'), $smtp ? 'ok' : ($production ? 'error' : 'warning'),
            $smtp ? self::str(config('mail.mailers.smtp.host')) : $mailer,
            $smtp ? self::t('Tekshirish: «Pochta» tabida «Test xat yuborish».') : self::t('Xatlar yuborilmaydi — «Pochta» tabida SMTP sozlang.'));
    }

    private function background(bool $production): void
    {
        $queue = self::str(config('queue.default'));
        $this->add('background', 'queue', self::t('Navbat drayveri'), $queue === 'sync' ? ($production ? 'error' : 'warning') : 'ok', $queue,
            $queue === 'sync' ? self::t('QUEUE_CONNECTION=database va Supervisor ishchilari kerak.') : null);

        $scheduler = $this->heartbeatAge(self::SCHEDULER_HEARTBEAT);
        $this->add('background', 'scheduler', self::t('Rejalashtiruvchi (cron)'),
            $scheduler !== null && $scheduler <= 180 ? 'ok' : ($production ? 'error' : 'warning'),
            $scheduler === null ? self::t('hali ishlamagan') : self::ago($scheduler),
            $scheduler !== null && $scheduler <= 180 ? null : self::t('Cron o\'rnating: * * * * * php artisan schedule:run (deploy/cron).'));

        $worker = $this->heartbeatAge(self::QUEUE_HEARTBEAT);
        $this->add('background', 'worker', self::t('Navbat ishchisi (worker)'),
            $worker !== null && $worker <= 900 ? 'ok' : ($production ? 'error' : 'warning'),
            $worker === null ? self::t("ma'lumot yo'q") : self::ago($worker),
            $worker !== null && $worker <= 900 ? null : self::t('sudo supervisorctl status — worker RUNNING bo\'lishi kerak; xatlar va AI so\'rovlari shunga bog\'liq.'));

        $failed = $this->failedJobs();
        $this->add('background', 'failed', self::t('Muvaffaqiyatsiz ishlar (7 kun)'), $failed > 0 ? 'warning' : 'ok', (string) $failed,
            $failed > 0 ? 'php artisan queue:failed · php artisan queue:retry all' : null);
    }

    private function journal(): void
    {
        $issn = self::str(config('journal.issn')) !== '' || self::str(config('journal.eissn')) !== '';
        $this->add('journal', 'issn', 'ISSN / e-ISSN', $issn ? 'ok' : 'warning',
            $issn ? trim(self::str(config('journal.issn')).' '.self::str(config('journal.eissn'))) : self::t('kiritilmagan'),
            $issn ? null : self::t('«Jurnal» tabida kiriting — saytda va Crossref/OAI\'da ishlatiladi.'));

        $doi = self::str(config('journal.doi_prefix'));
        $this->add('journal', 'doi', self::t('DOI prefiksi'), $doi !== '' ? 'ok' : 'warning', $doi !== '' ? $doi : self::t('kiritilmagan'),
            $doi !== '' ? null : self::t('Crossref a\'zoligidan keyin «Jurnal» tabida kiriting.'));

        $email = self::str(config('journal.contact.email'));
        $this->add('journal', 'email', self::t('Tahririyat emaili'), $email !== '' ? 'ok' : 'error', $email !== '' ? $email : self::t('kiritilmagan'),
            $email !== '' ? null : self::t('«Aloqa» tabida kiriting — aloqa formasi xatlari shu manzilga boradi.'));
    }

    private function content(): void
    {
        $subjects = $this->count(fn (): int => Subject::query()->active()->count());
        $this->add('content', 'subjects', self::t("Ilmiy yo'nalishlar"), $subjects > 0 ? 'ok' : 'error', (string) $subjects,
            $subjects > 0 ? null : self::t("Sozlamalar → Yo'nalishlar (yoki php artisan db:seed --class=SubjectSeeder)."));

        $types = $this->count(fn (): int => ArticleType::query()->where('is_active', true)->count());
        $this->add('content', 'types', self::t('Maqola turlari'), $types > 0 ? 'ok' : 'error', (string) $types,
            $types > 0 ? null : self::t('Sozlamalar → Maqola turlari va narxlar.'));

        $template = $this->count(fn (): int => JournalDocument::query()->active()->where('kind', JournalDocumentKind::Template->value)->count()) > 0
            || MediaUrl::publicAsset(config('journal.article_template')) !== null;
        $this->add('content', 'template', self::t('Maqola shabloni (Word)'), $template ? 'ok' : 'warning',
            $template ? self::t('yuklangan') : self::t("yo'q"), $template ? null : self::t('Sozlamalar → Fayllar → «Maqola shabloni».'));

        $board = $this->count(fn (): int => EditorialBoardMember::query()->where('is_active', true)->count());
        $this->add('content', 'board', self::t('Tahririyat kengashi'), $board > 0 ? 'ok' : 'warning',
            self::t(':count ta a\'zo', ['count' => (string) $board]), $board > 0 ? null : self::t('Sozlamalar → Tahririyat kengashi.'));

        $pages = $this->count(fn (): int => Page::query()->count());
        $this->add('content', 'pages', self::t('Statik sahifalar'), $pages >= 3 ? 'ok' : 'warning',
            self::t(':count / 3 tahrirlangan', ['count' => (string) $pages]),
            $pages >= 3 ? null : self::t('«Jurnal haqida», «Yo\'riqnoma», «Aloqa» hozir standart (qoralama) matnda — Sozlamalar → Sahifalar.'));
    }

    private function payment(bool $production): void
    {
        $paidTypes = $this->count(fn (): int => ArticleType::query()->where('is_active', true)->where('price', '>', 0)->count());
        $online = array_values(array_filter(PaymentProvider::online(), fn (PaymentProvider $p): bool => $this->payments->isEnabled($p)));
        $bank = self::str(config('journal.payment.account')) !== '' && self::str(config('journal.payment.bank')) !== '';

        $this->add('payment', 'methods', self::t("To'lov usullari"),
            $paidTypes === 0 || $online !== [] || $bank ? 'ok' : 'error',
            implode(', ', [...array_map(fn (PaymentProvider $p): string => $p->label(), $online), ...($bank ? [self::t('bank')] : [])]) ?: self::t("yo'q"),
            $paidTypes > 0 && $online === [] && ! $bank ? self::t("Pullik maqola turlari bor, lekin to'lov usuli yo'q — Click/Payme kalitlari yoki bank rekvizitlari kerak.") : null);

        $this->add('payment', 'requisites', self::t('Bank rekvizitlari'), $bank ? 'ok' : 'warning',
            $bank ? self::str(config('journal.payment.bank')) : self::t('kiritilmagan'),
            $bank ? null : self::t('«Rekvizitlar» tabida kiriting — bank orqali to\'lovchi mualliflar uchun.'));

        if ($this->payments->isEnabled(PaymentProvider::Payme)) {
            $test = (bool) config('payments.payme.test_mode');
            $this->add('payment', 'payme_mode', self::t('Payme rejimi'), $test ? ($production ? 'error' : 'warning') : 'ok',
                $test ? self::t('sinov kassasi') : self::t('ishchi'),
                $test ? self::t('Sinovlar tugagach PAYME_TEST_MODE=false va ishchi kalitni qo\'ying.') : null);
        }

        if ($this->payments->isEnabled(PaymentProvider::Click)) {
            $refund = self::str(config('payments.click.merchant_user_id')) !== '';
            $this->add('payment', 'click_refund', self::t('Click qaytarish (Merchant API)'), $refund ? 'ok' : 'warning',
                $refund ? self::t('sozlangan') : self::t('sozlanmagan'),
                $refund ? null : self::t('To\'lovni qaytarish uchun CLICK_MERCHANT_USER_ID kerak.'));
        }
    }

    private function security(bool $production): void
    {
        $admins = $this->users(fn () => User::query()->role(RoleName::SuperAdmin->value)->get());
        $this->add('security', 'super_admin', self::t('Bosh administrator'), $admins === [] ? 'error' : 'ok',
            (string) count($admins), $admins === [] ? 'php artisan app:create-super-admin' : null);

        $without = array_filter($admins, fn (User $u): bool => ! $u->hasEnabledTwoFactorAuthentication());
        $this->add('security', 'two_factor', self::t('Bosh administratorlarda 2FA'), $without === [] ? 'ok' : 'warning',
            $without === [] ? self::t('yoqilgan') : self::t(':count tasida yoqilmagan', ['count' => (string) count($without)]),
            $without === [] ? null : self::t('Sozlamalar → Xavfsizlik → «Ikki bosqichli himoya».'));

        $demo = $this->count(fn (): int => User::query()->where('email', 'like', '%@insonvajamiyat.test')->count());
        $this->add('security', 'demo', self::t('Demo hisoblar'), $demo === 0 ? 'ok' : ($production ? 'error' : 'warning'),
            (string) $demo, $demo === 0 ? null : self::t('«@insonvajamiyat.test» hisoblarini o\'chiring yoki bloklang (Foydalanuvchilar).'));
    }

    private function backup(): void
    {
        $settings = $this->backups->settings();
        $this->add('backup', 'schedule', self::t('Avtomatik zaxira'), $settings['enabled'] ? 'ok' : 'warning',
            $settings['enabled'] ? self::t('har kuni :time', ['time' => $settings['time']]) : self::t("o'chirilgan"),
            $settings['enabled'] ? null : self::t('Zaxira nusxa → «Avtomatik zaxira» jadvalini yoqing.'));

        $last = null;

        try {
            $last = Backup::query()->where('status', Backup::DONE)->latest('id')->first();
        } catch (Throwable) {
        }

        $age = $last?->finished_at !== null ? (int) $last->finished_at->diffInSeconds(now(), true) : null;
        $this->add('backup', 'last', self::t('Oxirgi muvaffaqiyatli zaxira'), $age !== null && $age <= 2 * 86400 ? 'ok' : 'warning',
            $age === null ? self::t("yo'q") : self::ago($age),
            $age !== null && $age <= 2 * 86400 ? null : self::t('Zaxira nusxa → «Zaxira nusxa yaratish».'));
    }

    private function integrations(): void
    {
        $this->add('integrations', 'ai', 'AI Studio', $this->ai->ready() ? 'ok' : 'off',
            $this->ai->ready() ? self::str($this->ai->model()) : self::t('sozlanmagan'),
            $this->ai->ready() ? null : self::t('AI Studio → Sozlamalar: API kalit.'));

        foreach (['google' => 'Google', 'orcid' => 'ORCID'] as $key => $label) {
            $ok = self::str(config("services.{$key}.client_id")) !== '' && self::str(config("services.{$key}.client_secret")) !== '';
            $this->add('integrations', $key, self::t(':provider orqali kirish', ['provider' => $label]), $ok ? 'ok' : 'off',
                $ok ? self::t('ulangan') : self::t('ulanmagan'), $ok ? null : self::t('.env: :prefix_CLIENT_ID va :prefix_CLIENT_SECRET', ['prefix' => strtoupper($key)]));
        }
    }

    /**
     * @param  'ok'|'warning'|'error'|'off'  $state
     */
    private function add(string $group, string $key, string $label, string $state, string $value, ?string $hint = null): void
    {
        $this->checks[] = ['key' => $key, 'group' => $group, 'label' => $label, 'state' => $state, 'value' => $value, 'hint' => $hint];
    }

    private function heartbeatAge(string $key): ?int
    {
        $value = Cache::get($key);

        return is_numeric($value) ? max(0, now()->getTimestamp() - (int) $value) : null;
    }

    private function pendingMigrations(): ?int
    {
        try {
            /** @var Migrator $migrator */
            $migrator = app('migrator');

            if (! $migrator->repositoryExists()) {
                return null;
            }

            $files = $migrator->getMigrationFiles([database_path('migrations')]);
            $ran = $migrator->getRepository()->getRan();

            return count(array_diff(array_keys($files), $ran));
        } catch (Throwable) {
            return null;
        }
    }

    private function failedJobs(): int
    {
        try {
            return Schema::hasTable('failed_jobs')
                ? DB::table('failed_jobs')->where('failed_at', '>=', now()->subDays(7))->count()
                : 0;
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * @param  callable(): int  $query
     */
    private function count(callable $query): int
    {
        try {
            return $query();
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * @param  callable(): iterable<User>  $query
     * @return list<User>
     */
    private function users(callable $query): array
    {
        try {
            $users = [];

            foreach ($query() as $user) {
                $users[] = $user;
            }

            return $users;
        } catch (Throwable) {
            return [];
        }
    }

    private static function ago(int $seconds): string
    {
        // ru.json da ko'plik shakllari ("минуту|минуты|минут") — trans_choice
        [$key, $count] = match (true) {
            $seconds < 120 => ['hozirgina', 0],
            $seconds < 7200 => [':count daqiqa oldin', intdiv($seconds, 60)],
            $seconds < 172800 => [':count soat oldin', intdiv($seconds, 3600)],
            default => [':count kun oldin', intdiv($seconds, 86400)],
        };

        // O'zbekcha manba matn lug'atda yo'q — trans_choice uni fallback (en) ga almashtirib yubormasligi uchun
        return Lang::hasForLocale($key)
            ? trans_choice($key, $count, ['count' => $count])
            : self::t($key, ['count' => (string) $count]);
    }

    /**
     * @param  array<string, string>  $replace
     */
    private static function t(string $key, array $replace = []): string
    {
        $text = __($key, $replace);

        return is_string($text) ? $text : $key;
    }

    private static function str(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
