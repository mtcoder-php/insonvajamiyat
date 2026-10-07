<?php

namespace App\Services\Settings;

use App\Enums\AuditEvent;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\System\MailTestNotification;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Admin → Tizim sozlamalari: jurnal rekvizitlari va pochta (SMTP).
 *
 * Qiymatlar settings jadvalida ("journal" va "mail" guruhlari) saqlanadi va ilova
 * ishga tushganda config() ustidan yoziladi (AppServiceProvider → apply()).
 * Jadvalda yozuv bo'lmasa — .env / config qiymati ishlaydi.
 * O'qilgan qatorlar keshda (parol shifrlangan holda) saqlanadi, saqlashda kesh tozalanadi.
 */
class SystemSettings
{
    public const CACHE_KEY = 'system-settings:v1';

    /** Kalit → config yo'li */
    public const JOURNAL = [
        'name' => 'journal.name',
        'subtitle' => 'journal.subtitle',
        'description' => 'journal.description',
        'issn' => 'journal.issn',
        'eissn' => 'journal.eissn',
        'doi_prefix' => 'journal.doi_prefix',
        'frequency' => 'journal.frequency',
        'plagiarism_max' => 'journal.plagiarism_max',
        'contact_email' => 'journal.contact.email',
        'contact_phone' => 'journal.contact.phone',
        'contact_address' => 'journal.contact.address',
        'social_telegram' => 'journal.socials.telegram',
        'social_facebook' => 'journal.socials.facebook',
        'social_instagram' => 'journal.socials.instagram',
        'social_youtube' => 'journal.socials.youtube',
        'social_linkedin' => 'journal.socials.linkedin',
        'payment_recipient' => 'journal.payment.recipient',
        'payment_bank' => 'journal.payment.bank',
        'payment_account' => 'journal.payment.account',
        'payment_mfo' => 'journal.payment.mfo',
        'payment_inn' => 'journal.payment.inn',
    ];

    public const MAIL = [
        'mailer' => 'mail.default',
        'host' => 'mail.mailers.smtp.host',
        'port' => 'mail.mailers.smtp.port',
        'scheme' => 'mail.mailers.smtp.scheme',
        'username' => 'mail.mailers.smtp.username',
        'password' => 'mail.mailers.smtp.password',
        'from_address' => 'mail.from.address',
        'from_name' => 'mail.from.name',
    ];

    private const NUMERIC = ['plagiarism_max' => 'float', 'port' => 'int'];

    public function __construct(
        private readonly SettingsStore $store,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Saqlangan qiymatlarni config() ga yozish. Jadval hali yo'q bo'lsa (o'rnatish,
     * migratsiyadan oldin) — jimgina o'tkazib yuboriladi.
     */
    public static function apply(bool $fresh = false): void
    {
        if ($fresh) {
            Cache::forget(self::CACHE_KEY);
        }

        try {
            /** @var array<string, array{value: string|null, encrypted: bool}> $rows */
            $rows = Cache::rememberForever(self::CACHE_KEY, fn (): array => Setting::query()
                ->whereIn('group', ['journal', 'mail'])
                ->get()
                ->mapWithKeys(fn (Setting $s): array => [$s->group.'.'.$s->key => ['value' => $s->value, 'encrypted' => $s->is_encrypted]])
                ->all());
        } catch (Throwable) {
            return;
        }

        $values = [];

        foreach (['journal' => self::JOURNAL, 'mail' => self::MAIL] as $group => $map) {
            foreach ($map as $key => $path) {
                if (! array_key_exists($group.'.'.$key, $rows)) {
                    continue;
                }

                $row = $rows[$group.'.'.$key];
                $value = $row['value'];

                if ($row['encrypted'] && $value !== null && $value !== '') {
                    try {
                        $value = Crypt::decryptString($value);
                    } catch (DecryptException) {
                        continue;
                    }
                }

                $values[$path] = self::cast($key, $value);
            }
        }

        if ($values !== []) {
            config($values);
        }
    }

    /**
     * Forma uchun joriy (amaldagi) qiymatlar. Parol hech qachon qaytarilmaydi.
     *
     * @return array{journalForm: array<string, string|int|float|null>, mailForm: array<string, string|int|float|null>, mailPassword: array{set: bool, source: string}}
     */
    public function form(): array
    {
        $journal = [];

        foreach (self::JOURNAL as $key => $path) {
            $journal[$key] = self::scalar(config($path));
        }

        $mail = [];

        foreach (self::MAIL as $key => $path) {
            if ($key !== 'password') {
                $mail[$key] = self::scalar(config($path));
            }
        }

        $mail['mailer'] = in_array($mail['mailer'], ['smtp', 'log'], true) ? $mail['mailer'] : 'log';
        $mail['scheme'] = $mail['scheme'] === 'smtps' ? 'smtps' : 'smtp';

        $password = config('mail.mailers.smtp.password');

        return [
            'journalForm' => $journal,
            'mailForm' => $mail,
            'mailPassword' => [
                'set' => is_string($password) && $password !== '',
                'source' => $this->store->has('mail', 'password') ? 'database' : (is_string($password) && $password !== '' ? 'env' : 'none'),
            ],
        ];
    }

    /**
     * @param  'journal'|'mail'  $group
     * @param  array<string, mixed>  $data
     * @return array<int, string> o'zgargan kalitlar
     */
    public function update(string $group, array $data, User $actor): array
    {
        $map = $group === 'journal' ? self::JOURNAL : self::MAIL;
        $changed = [];

        foreach ($map as $key => $path) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];
            $value = is_string($value) ? trim($value) : $value;

            if ($key === 'password') {
                if (! is_string($value) || $value === '') {
                    continue; // bo'sh — o'zgarishsiz
                }
            }

            $old = self::scalar(config($path));
            $new = self::cast($key, is_scalar($value) ? (string) $value : null);

            if ($key !== 'password' && $old == $new) {
                continue;
            }

            $this->store->set($group, $key, $new === null ? '' : (string) $new, $actor, encrypted: $key === 'password');
            $changed[] = $key;
        }

        if ($changed === []) {
            return [];
        }

        self::apply(fresh: true);

        if ($group === 'mail') {
            Mail::forgetMailers();
            // Navbatdagi worker'lar yangi SMTP sozlamalari bilan qayta ishga tushadi
            Artisan::call('queue:restart');
        }

        $this->audit->log(AuditEvent::SettingsUpdated, null, ['group' => $group, 'keys' => $changed], actor: $actor);

        return $changed;
    }

    /**
     * Saqlangan sozlamalar bilan test xat yuborish (navbatsiz).
     */
    public function sendTest(string $to, User $actor): void
    {
        Mail::forgetMailers();

        try {
            Notification::route('mail', $to)->notifyNow(new MailTestNotification($actor->name));
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'test_email' => __('Xat yuborilmadi: :error', ['error' => mb_substr(self::sanitize($e->getMessage()), 0, 300)]),
            ]);
        }

        $this->audit->log(AuditEvent::MailTestSent, null, [
            'to' => $to,
            'mailer' => self::scalar(config('mail.default')),
            'host' => self::scalar(config('mail.mailers.smtp.host')),
        ], actor: $actor);
    }

    private static function cast(string $key, ?string $value): string|int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match (self::NUMERIC[$key] ?? null) {
            'int' => (int) $value,
            'float' => (float) $value,
            default => $value,
        };
    }

    private static function scalar(mixed $value): string|int|float|null
    {
        return is_string($value) || is_int($value) || is_float($value) ? $value : null;
    }

    /** Xato matnidan parolni yashirish (SMTP xatolarida ba'zan login ma'lumotlari chiqadi) */
    private static function sanitize(string $message): string
    {
        $password = config('mail.mailers.smtp.password');

        return is_string($password) && $password !== '' ? str_replace($password, '••••', $message) : $message;
    }
}
