<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Maqolaning tahrir/tarjima versiyalari tarixi (TZ 4.2.2 "versiyalash").
 * Har bir versiyaga article_files orqali fayl(lar) bog'lanadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version_number');
            $table->string('type', 30);                       // App\Enums\ArticleVersionType
            $table->unsignedTinyInteger('review_round')->default(0);
            $table->char('language', 2)->default('uz');
            $table->longText('content')->nullable();          // Matn (AI tekshiruv/tarjima natijasi)
            $table->text('change_note')->nullable();          // Muallifning "nimalar o'zgardi" izohi
            $table->foreignId('ai_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['article_id', 'version_number']);
            $table->index(['article_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_versions');
    }
};
