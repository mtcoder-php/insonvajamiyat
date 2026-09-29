<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Muharrir izohlari" / "Tahririyat izohlari" — faqat xodimlar ko'radigan ichki izohlar.
 * Muallifga yoziladigan xabarlar messages jadvalida (bu yerda emas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['article_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_notes');
    }
};
