<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Taqrizchini vaqtincha to'xtatish (ta'til, band): to'xtatilgan taqrizchiga
 * yangi taklif yuborilmaydi, joriy taqrizlari davom etadi.
 * Taqrizchi yo'nalishlari — mavjud author_subjects jadvalida (user ↔ subject).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('reviews_paused_at')->nullable()->after('ai_monthly_token_limit');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('reviews_paused_at');
        });
    }
};
