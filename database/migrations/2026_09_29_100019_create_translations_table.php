<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Muallifning tarjima "hujjati" (TZ 4.1.6). Har bir tahrir/qayta tarjima
 * translation_versions'da alohida versiya bo'lib saqlanadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->char('source_language', 2)->default('uz');
            $table->char('target_language', 2);
            $table->longText('source_text');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
