<?php

namespace App\Console\Commands;

use App\Services\Settings\LaunchReadiness;
use Illuminate\Console\Command;

/**
 * Ishga tushirishdan oldin tekshiruv (admin → Tizim sozlamalari → Tizim holati bilan bir xil).
 * Xato (✖) bo'lsa 1 bilan tugaydi — deploy skripti yoki CI'da ishlatish mumkin.
 */
class LaunchCheck extends Command
{
    protected $signature = 'app:launch-check {--json : Natijani JSON ko\'rinishida chiqarish}';

    protected $description = 'Saytni ishga tushirishga tayyorligini tekshirish (sozlamalar, fon jarayonlari, kontent, xavfsizlik)';

    private const ICONS = [
        'ok' => '<fg=green>✔</>',
        'warning' => '<fg=yellow>!</>',
        'error' => '<fg=red>✖</>',
        'off' => '<fg=gray>–</>',
    ];

    public function handle(LaunchReadiness $readiness): int
    {
        $report = $readiness->report();

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return $report['ready'] ? self::SUCCESS : self::FAILURE;
        }

        foreach ($report['groups'] as $group) {
            $this->newLine();
            $this->line('<options=bold>'.$group['label'].'</>');

            foreach ($group['checks'] as $check) {
                $this->line(sprintf('  %s  %s: <fg=white>%s</>', self::ICONS[$check['state']], $check['label'], $check['value']));

                if ($check['hint'] !== null && $check['state'] !== 'ok') {
                    $this->line('       <fg=gray>'.$check['hint'].'</>');
                }
            }
        }

        $counts = $report['counts'];
        $this->newLine();
        $summary = sprintf('Joyida: %d · Tavsiya: %d · Xato: %d · Ulanmagan: %d', $counts['ok'], $counts['warning'], $counts['error'], $counts['off']);

        if ($report['ready']) {
            $this->info('Ishga tushirishga tayyor. '.$summary);

            return self::SUCCESS;
        }

        $this->error('Ishga tushirishdan oldin xatolarni tuzating. '.$summary);

        return self::FAILURE;
    }
}
