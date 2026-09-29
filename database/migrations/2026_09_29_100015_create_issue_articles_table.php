<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Son ichidagi maqolalar tartibi (pivot). Bitta maqola faqat bitta songa kiradi
 * → article_id unique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->unique()->constrained()->restrictOnDelete();
            $table->json('section')->nullable();                  // Rukn nomi: {"uz":"Falsafa", ...}
            $table->unsignedSmallInteger('position')->default(0);
            $table->unsignedSmallInteger('page_from')->nullable();
            $table->unsignedSmallInteger('page_to')->nullable();
            $table->timestamps();

            $table->index(['journal_issue_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_articles');
    }
};
