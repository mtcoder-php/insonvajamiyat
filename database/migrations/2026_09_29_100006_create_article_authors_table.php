<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Maqola mualliflari (hammualliflar bilan).
 * Hammuallif tizimda ro'yxatdan o'tmagan bo'lishi mumkin → user_id nullable.
 * Ma'lumotlar yuborish paytidagi holatda saqlanadi (nashrdan keyin profil
 * o'zgarsa ham maqoladagi afiliatsiya o'zgarmasligi uchun).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('last_name', 100);
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('organization')->nullable();
            $table->string('position')->nullable();
            $table->string('academic_degree', 100)->nullable();
            $table->string('orcid', 19)->nullable();
            $table->char('country', 2)->nullable();

            $table->boolean('is_corresponding')->default(false); // Aloqa uchun mas'ul muallif
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['article_id', 'sort_order']);
            $table->index(['last_name', 'first_name']);   // Muallif bo'yicha qidiruv
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_authors');
    }
};
