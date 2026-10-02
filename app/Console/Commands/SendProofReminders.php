<?php

namespace App\Console\Commands;

use App\Services\Production\ProductionService;
use Illuminate\Console\Command;

/**
 * Korrektura muddati bo'yicha eslatmalar (har soatda, routes/console.php):
 * muddat tugashiga oz qolganda — muallifga, muddat o'tganda — maketchi va bosh muharrirlarga.
 * Har bir xabar bir marta yuboriladi (production_checklist'da belgi qoladi).
 */
class SendProofReminders extends Command
{
    protected $signature = 'app:proof-reminders';

    protected $description = "Korrektura muddati tugayotgan / o'tgan maqolalar bo'yicha eslatmalar";

    public function handle(ProductionService $production): int
    {
        $counts = $production->sendProofReminders();

        $this->info("Muallifga eslatma: {$counts['reminded']} ta, muddati o'tgan: {$counts['overdue']} ta.");

        return self::SUCCESS;
    }
}
