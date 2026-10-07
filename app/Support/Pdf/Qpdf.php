<?php

namespace App\Support\Pdf;

use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * qpdf (https://qpdf.readthedocs.io) — PDF fayllarni birlashtirish, xatcho'plar va sahifa belgilarini qo'shish.
 * Serverda o'rnatiladi: `sudo apt install qpdf` (11.0 va undan yangi versiya kerak).
 */
final class Qpdf
{
    public const MIN_MAJOR = 11;

    /** So'rov davomida versiya bir marta aniqlanadi (false — hali tekshirilmagan) */
    private static string|null|false $version = false;

    public static function binary(): string
    {
        $binary = config('journal.pdf.qpdf');

        return is_string($binary) && $binary !== '' ? $binary : 'qpdf';
    }

    /** O'rnatilgan versiya (masalan "11.9.0") yoki null */
    public static function version(): ?string
    {
        if (self::$version !== false) {
            return self::$version;
        }

        return self::$version = self::detect();
    }

    public static function flush(): void
    {
        self::$version = false;
    }

    private static function detect(): ?string
    {
        try {
            $result = Process::timeout(10)->run([self::binary(), '--version']);
        } catch (\Throwable) {
            return null;
        }

        if (! $result->successful() || preg_match('/qpdf version (\d+\.\d+(?:\.\d+)?)/i', $result->output(), $m) !== 1) {
            return null;
        }

        return $m[1];
    }

    public static function available(): bool
    {
        $version = self::version();

        return $version !== null && (int) explode('.', $version)[0] >= self::MIN_MAJOR;
    }

    /**
     * @param  array<int, string>  $files
     */
    public static function merge(array $files, string $output): void
    {
        self::run([self::binary(), '--empty', '--pages', ...$files, '--', $output]);
    }

    /**
     * Sahifalar va obyektlar (JSON v2, oqim ma'lumotlarisiz).
     *
     * @return array<string, mixed>
     */
    public static function inspect(string $file): array
    {
        $output = self::run([
            self::binary(), '--json=2', '--json-key=pages', '--json-key=qpdf', '--json-stream-data=none', $file,
        ]);

        $data = json_decode($output, true);

        if (! is_array($data)) {
            throw new RuntimeException('qpdf JSON output could not be parsed.');
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * qpdf JSON (v2) yangilanishini qo'llash: yangi obyektlar qo'shiladi, mavjudlari almashtiriladi.
     *
     * @param  array<string, mixed>  $objects  "obj:N 0 R" => ['value' => …]
     */
    public static function update(string $input, array $objects, string $output): void
    {
        $json = tempnam(sys_get_temp_dir(), 'qpdfjson');

        if ($json === false) {
            throw new RuntimeException('Temporary file could not be created.');
        }

        try {
            file_put_contents($json, (string) json_encode(['qpdf' => [['jsonversion' => 2], $objects]], JSON_UNESCAPED_UNICODE));
            self::run([self::binary(), $input, '--update-from-json='.$json, $output]);
        } finally {
            @unlink($json);
        }
    }

    /**
     * @param  array<int, string>  $command
     */
    private static function run(array $command): string
    {
        $timeout = (int) config('journal.pdf.timeout', 300);
        $result = Process::timeout($timeout)->run($command);

        // 3 — ogohlantirishlar bilan muvaffaqiyatli (masalan, biroz buzilgan, lekin tiklangan PDF)
        if (! in_array($result->exitCode(), [0, 3], true)) {
            throw new RuntimeException(trim($result->errorOutput()) ?: 'qpdf failed with exit code '.$result->exitCode());
        }

        return $result->output();
    }
}
