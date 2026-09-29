<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Maqolani baholash" (1–5 yulduz). Bitta foydalanuvchi — bitta baho (yangilash mumkin).
 * articles.rating_avg / ratings_count Service qatlamida qayta hisoblanadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');            // 1–5
            $table->timestamps();

            $table->unique(['article_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_ratings');
    }
};
