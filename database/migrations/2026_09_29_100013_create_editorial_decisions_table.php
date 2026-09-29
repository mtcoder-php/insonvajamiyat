<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Muharrir qarorlari — taqriz natijalari asosida (qabul / tuzatish / rad).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editorial_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('editor_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('round')->default(1);
            $table->string('decision', 30);                   // App\Enums\EditorialDecisionType
            $table->text('comment_to_author')->nullable();
            $table->text('internal_note')->nullable();
            $table->timestamps();

            $table->index(['article_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editorial_decisions');
    }
};
