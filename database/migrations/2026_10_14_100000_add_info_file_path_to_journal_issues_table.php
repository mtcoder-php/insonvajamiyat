<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Son PDF ining 2-beti — "jurnal ma'lumotlari" sahifasi (tahririyat maketi, PDF yoki rasm).
 * Yuklanmasa, tizim standart sahifani o'zi chizadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_issues', function (Blueprint $table) {
            $table->string('info_file_path')->nullable()->after('toc_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('journal_issues', function (Blueprint $table) {
            $table->dropColumn('info_file_path');
        });
    }
};
