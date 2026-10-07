<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * To'liq son PDF ni avtomatik yig'ish holati (muqova + mundarija + maqolalar).
 *   pdf_status: queued | processing | done | failed (null — hali yig'ilmagan yoki qo'lda yuklangan)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_issues', function (Blueprint $table) {
            $table->string('pdf_status', 20)->nullable()->after('full_pdf_path');
            $table->text('pdf_error')->nullable()->after('pdf_status');
            $table->unsignedSmallInteger('pdf_pages')->nullable()->after('pdf_error');
            $table->boolean('pdf_auto')->default(false)->after('pdf_pages');
            $table->timestamp('pdf_built_at')->nullable()->after('pdf_auto');
        });
    }

    public function down(): void
    {
        Schema::table('journal_issues', function (Blueprint $table) {
            $table->dropColumn(['pdf_status', 'pdf_error', 'pdf_pages', 'pdf_auto', 'pdf_built_at']);
        });
    }
};
