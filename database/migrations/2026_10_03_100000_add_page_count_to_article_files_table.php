<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PDF fayllarning betlar soni (App\Support\PdfPageCounter yuklashda aniqlaydi).
 * Yakuniy PDF dagi qiymat maqolaning pages_count iga va son sahifalarini hisoblashga ishlatiladi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_files', function (Blueprint $table) {
            $table->unsignedSmallInteger('page_count')->nullable()->after('size');
        });
    }

    public function down(): void
    {
        Schema::table('article_files', function (Blueprint $table) {
            $table->dropColumn('page_count');
        });
    }
};
