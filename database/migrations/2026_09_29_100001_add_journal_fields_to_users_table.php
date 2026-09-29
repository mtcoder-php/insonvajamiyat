<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel'ning standart users jadvaliga jurnal uchun kerakli maydonlar.
 * (2FA ustunlari Fortify migratsiyasi orqali alohida qo'shiladi.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Google/ORCID orqali kirganlarda parol bo'lmasligi mumkin
            $table->string('password')->nullable()->change();

            $table->string('phone', 20)->nullable()->after('email');
            $table->char('locale', 2)->default('uz')->after('phone');

            // Bloklash (TZ 4.2.4)
            $table->boolean('is_blocked')->default(false)->after('locale');
            $table->timestamp('blocked_at')->nullable()->after('is_blocked');
            $table->string('blocked_reason', 500)->nullable()->after('blocked_at');

            // AI oylik token limiti: null → settings('ai.default_monthly_token_limit')
            $table->unsignedInteger('ai_monthly_token_limit')->nullable()->after('blocked_reason');

            // Audit
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');

            $table->softDeletes();

            $table->index('is_blocked');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_blocked']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'phone', 'locale', 'is_blocked', 'blocked_at', 'blocked_reason',
                'ai_monthly_token_limit', 'last_login_at', 'last_login_ip',
            ]);
            $table->string('password')->nullable(false)->change();
        });
    }
};
