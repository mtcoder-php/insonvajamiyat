<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Maqola bo'yicha kunlik ko'rish/yuklab olish statistikasi (TZ 4.2.6).
 * Har bir ko'rish uchun alohida qator yozmaslik uchun kunlik agregat:
 * INSERT ... ON DUPLICATE KEY UPDATE views = views + 1.
 * articles.views_count / downloads_count — tezkor ko'rsatish uchun umumiy hisoblagich.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('downloads')->default(0);

            $table->unique(['article_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_daily_stats');
    }
};
