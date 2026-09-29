<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Maqolaga tegishli fayllar: asosiy fayl, ilovalar, tuzatishlar, yakuniy PDF.
 * Fayllar private diskda saqlanadi, yuklab olish faqat Policy orqali.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();                   // Yuklab olish havolasi uchun
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_version_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type', 30);                       // App\Enums\ArticleFileType
            $table->string('disk', 30)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size');               // bayt
            $table->char('checksum', 64)->nullable();         // sha256 — dublikat va butunlik nazorati

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['article_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_files');
    }
};
