<?php

use App\Models\AuditLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Audit log: saqlash muddati (journal.audit.retention_days) o'tgan yozuvlarni tozalash
Schedule::command('model:prune', ['--model' => [AuditLog::class]])->dailyAt('03:15');

// Korrektura muddati: muallifga eslatma va muddat o'tganda tahririyatga xabar
Schedule::command('app:proof-reminders')->hourlyAt(7);

// To'lov kutilayotgan maqolalar: mualliflarga eslatma (Toshkent vaqti bilan 10:00)
Schedule::command('app:payment-reminders')->dailyAt('05:00');

// Zaxira nusxa: admin paneldagi jadval (Admin → Zaxira nusxa) bo'yicha — vaqti kelganini o'zi tekshiradi
Schedule::command('backup:run --scheduled')->everyTenMinutes()->withoutOverlapping();
