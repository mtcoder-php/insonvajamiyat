<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jurnal sonining DOI raqami (bosh sahifadagi "So'nggi son" kartasi, son sahifasi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_issues', function (Blueprint $table) {
            $table->string('doi')->nullable()->unique()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('journal_issues', function (Blueprint $table) {
            $table->dropUnique(['doi']);
            $table->dropColumn('doi');
        });
    }
};
