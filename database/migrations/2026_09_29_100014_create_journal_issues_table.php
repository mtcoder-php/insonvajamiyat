<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jurnal sonlari (TZ 4.2.3). status=published bo'lgach web arxivda ko'rinadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedSmallInteger('volume')->nullable();   // Jild (tom)
            $table->unsignedSmallInteger('number');               // Son raqami
            $table->string('slug')->unique();                     // 2026-3
            $table->json('title')->nullable();                    // Maxsus son nomi (ixtiyoriy)
            $table->json('description')->nullable();

            $table->string('cover_image_path')->nullable();       // Muqova
            $table->string('toc_file_path')->nullable();          // Generatsiya qilingan mundarija PDF
            $table->string('full_pdf_path')->nullable();          // Butun son PDF

            $table->string('status', 20)->default('draft');       // App\Enums\IssueStatus
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['year', 'number']);
            $table->index(['status', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_issues');
    }
};
