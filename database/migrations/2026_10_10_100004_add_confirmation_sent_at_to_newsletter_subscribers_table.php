<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Obunani tasdiqlash xati (double opt-in) qachon yuborilgani — qayta yuborishni cheklash
 * va tasdiqlanmagan eski yozuvlarni tozalash uchun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->timestamp('confirmation_sent_at')->nullable()->after('token');
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->dropColumn('confirmation_sent_at');
        });
    }
};
