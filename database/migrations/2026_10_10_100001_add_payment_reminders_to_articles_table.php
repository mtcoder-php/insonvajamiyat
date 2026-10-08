<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * To'lov eslatmalari (TZ 4.2.8): "To'lov kutilmoqda" holatidagi maqola muallifiga
 * yuborilgan eslatmalar soni va oxirgi vaqti (avtomatik jadval va qo'lda yuborish).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->unsignedTinyInteger('payment_reminders_count')->default(0)->after('payment_status');
            $table->timestamp('payment_reminded_at')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['payment_reminders_count', 'payment_reminded_at']);
        });
    }
};
