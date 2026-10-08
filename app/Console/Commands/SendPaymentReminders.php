<?php

namespace App\Console\Commands;

use App\Services\Payments\PaymentReminderService;
use Illuminate\Console\Command;

/**
 * To'lov kutilayotgan maqolalar mualliflariga avtomatik eslatma (har kuni, routes/console.php).
 * Jadval: config('journal.payment_reminders.days') — yuborilganidan keyin shu kunlarda, bir martadan.
 */
class SendPaymentReminders extends Command
{
    protected $signature = 'app:payment-reminders';

    protected $description = "To'lov kutilayotgan maqolalar mualliflariga eslatma yuborish";

    public function handle(PaymentReminderService $reminders): int
    {
        $sent = $reminders->sendDue();

        $this->info("To'lov eslatmasi: {$sent} ta.");

        return self::SUCCESS;
    }
}
