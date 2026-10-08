<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * To'lovni qaytarish (TZ 4.1.4, 4.2.8): kim yakunladi va bank hujjati raqami
 * (qo'lda tasdiqlangan to'lov bank orqali qaytarilganda).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->string('reference', 100)->nullable()->after('provider_refund_id');
            $table->foreignId('processed_by')->nullable()->after('requested_by')->constrained('users')->nullOnDelete();
            $table->index(['payment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropIndex(['payment_id', 'status']);
            $table->dropConstrainedForeignId('processed_by');
            $table->dropColumn('reference');
        });
    }
};
