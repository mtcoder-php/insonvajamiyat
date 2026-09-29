<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ommaviy yuklab olinadigan fayllar: Word shablon, yo'riqnoma PDF,
 * litsenziya shartnomasi va h.k. (TZ 4.1.1, 4.1.3, 4.2.5).
 * Har bir fayl tilga ko'ra alohida bo'lishi mumkin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_files', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100);                       // article_template, author_guide, license_agreement
            $table->char('language', 2)->default('uz');
            $table->json('title');
            $table->json('description')->nullable();
            $table->string('disk', 30)->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('downloads_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['key', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_files');
    }
};
