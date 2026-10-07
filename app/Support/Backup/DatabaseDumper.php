<?php

namespace App\Support\Backup;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use PDO;
use RuntimeException;

/**
 * Ma'lumotlar bazasini SQL faylga yozish:
 *   MySQL / MariaDB — mysqldump (parol MYSQL_PWD orqali, buyruq qatorida ko'rinmaydi);
 *   PostgreSQL      — pg_dump (PGPASSWORD);
 *   SQLite          — PHP orqali (sxema + INSERT), tranzaksiya ichida ham ishlaydi.
 */
final class DatabaseDumper
{
    public function __construct(private readonly ?string $connection = null) {}

    public function driver(): string
    {
        return $this->db()->getDriverName();
    }

    /**
     * SQL ni $path ga yozadi.
     */
    public function dump(string $path): void
    {
        $driver = $this->driver();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $this->mysql($path);
        } elseif ($driver === 'pgsql') {
            $this->pgsql($path);
        } elseif ($driver === 'sqlite') {
            $this->sqlite($path);
        } else {
            throw new RuntimeException('Bu baza turi qo\'llab-quvvatlanmaydi: '.$driver);
        }

        if (! is_file($path) || filesize($path) === 0) {
            throw new RuntimeException("Baza nusxasi bo'sh chiqdi.");
        }
    }

    /**
     * SQL faylni gzip qilib, asl faylni o'chiradi.
     */
    public static function gzip(string $source, string $target): void
    {
        $in = fopen($source, 'rb');
        $out = gzopen($target, 'wb6');

        if ($in === false || $out === false) {
            throw new RuntimeException('Arxivlab bo\'lmadi (gzip).');
        }

        while (! feof($in)) {
            $chunk = fread($in, 1024 * 512);

            if ($chunk === false) {
                break;
            }

            gzwrite($out, $chunk);
        }

        fclose($in);
        gzclose($out);
        @unlink($source);
    }

    private function db(): Connection
    {
        $connection = DB::connection($this->connection);

        if (! $connection instanceof Connection) {
            throw new RuntimeException('Baza ulanishi topilmadi.');
        }

        return $connection;
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        $config = $this->db()->getConfig();

        return is_array($config) ? $config : [];
    }

    private function mysql(string $path): void
    {
        $c = $this->config();
        $binary = self::str(config('backup.mysqldump')) ?: 'mysqldump';

        $command = [
            $binary,
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--no-tablespaces',
            '--default-character-set=utf8mb4',
            '--user='.self::str($c['username'] ?? ''),
            '--result-file='.$path,
        ];

        $socket = self::str($c['unix_socket'] ?? '');

        if ($socket !== '') {
            $command[] = '--socket='.$socket;
        } else {
            $command[] = '--host='.(self::str($c['host'] ?? '') ?: '127.0.0.1');
            $command[] = '--port='.(self::str($c['port'] ?? '') ?: '3306');
        }

        $command[] = self::str($c['database'] ?? '');

        $this->run($command, ['MYSQL_PWD' => self::str($c['password'] ?? '')], 'mysqldump');
    }

    private function pgsql(string $path): void
    {
        $c = $this->config();
        $binary = self::str(config('backup.pg_dump')) ?: 'pg_dump';

        $this->run([
            $binary,
            '--no-owner',
            '--no-privileges',
            '--host='.(self::str($c['host'] ?? '') ?: '127.0.0.1'),
            '--port='.(self::str($c['port'] ?? '') ?: '5432'),
            '--username='.self::str($c['username'] ?? ''),
            '--file='.$path,
            self::str($c['database'] ?? ''),
        ], ['PGPASSWORD' => self::str($c['password'] ?? '')], 'pg_dump');
    }

    /**
     * @param  array<int, string>  $command
     * @param  array<string, string>  $env
     */
    private function run(array $command, array $env, string $tool): void
    {
        $result = Process::env($env)
            ->timeout(max(60, (int) config('backup.timeout', 1800)))
            ->run($command);

        if (! $result->successful()) {
            $error = trim($result->errorOutput()) ?: trim($result->output());

            if ($result->exitCode() === 127 || str_contains($error, 'not found')) {
                throw new RuntimeException("{$tool} topilmadi. Serverga o'rnating: sudo apt install ".($tool === 'pg_dump' ? 'postgresql-client' : 'mysql-client'));
            }

            throw new RuntimeException("{$tool} xatosi: ".mb_substr($error, 0, 500));
        }
    }

    private function sqlite(string $path): void
    {
        $pdo = $this->db()->getPdo();
        $out = fopen($path, 'wb');

        if ($out === false) {
            throw new RuntimeException('Faylga yozib bo\'lmadi: '.$path);
        }

        fwrite($out, "-- SQLite zaxira nusxa\nPRAGMA foreign_keys=OFF;\nBEGIN TRANSACTION;\n");

        $objects = $pdo->query("SELECT type, name, sql FROM sqlite_master WHERE sql IS NOT NULL AND name NOT LIKE 'sqlite_%' ORDER BY CASE type WHEN 'table' THEN 0 WHEN 'index' THEN 1 ELSE 2 END, name");
        $rows = $objects !== false ? $objects->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($rows as $object) {
            $sql = is_string($object['sql'] ?? null) ? $object['sql'] : '';
            fwrite($out, $sql.";\n");

            if (($object['type'] ?? null) !== 'table' || ! is_string($object['name'] ?? null)) {
                continue;
            }

            $table = $object['name'];
            $quoted = self::quoteIdentifier($table);

            // Hisoblanadigan (generated) ustunlar table_info da yo'q — ularni yozmaymiz
            $info = $pdo->query('PRAGMA table_info('.$quoted.')');
            $columns = $info !== false
                ? array_values(array_filter(array_map(
                    fn (mixed $c): string => is_array($c) && is_string($c['name'] ?? null) ? $c['name'] : '',
                    $info->fetchAll(PDO::FETCH_ASSOC),
                )))
                : [];

            if ($columns === []) {
                continue;
            }

            $list = implode(',', array_map(fn (string $c): string => self::quoteIdentifier($c), $columns));
            $data = $pdo->query('SELECT '.$list.' FROM '.$quoted);

            if ($data === false) {
                continue;
            }

            while (($row = $data->fetch(PDO::FETCH_NUM)) !== false) {
                if (! is_array($row)) {
                    continue;
                }

                $values = array_map(fn (mixed $v): string => match (true) {
                    $v === null => 'NULL',
                    is_int($v), is_float($v) => (string) $v,
                    default => (string) $pdo->quote(is_scalar($v) ? (string) $v : ''),
                }, $row);

                fwrite($out, 'INSERT INTO '.$quoted.' ('.$list.') VALUES('.implode(',', $values).");\n");
            }
        }

        fwrite($out, "COMMIT;\n");
        fclose($out);
    }

    private static function quoteIdentifier(string $name): string
    {
        return '"'.str_replace('"', '""', $name).'"';
    }

    private static function str(mixed $value): string
    {
        return is_string($value) || is_int($value) ? (string) $value : '';
    }
}
