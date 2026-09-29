<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Maqola bo'yicha yozishmalar (TZ 4.1.3, 4.2.2).
 *  - author_editor   : muallif ↔ tahririyat
 *  - editor_reviewer : tahririyat ↔ aniq bir taqrizchi (review_id orqali)
 * Muallif va taqrizchi to'g'ridan-to'g'ri yozisha olmaydi (blind review).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('review_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('channel', 30);                    // App\Enums\MessageChannel
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->timestamp('read_at')->nullable();         // Qarshi tomon o'qigan vaqt
            $table->timestamps();

            $table->index(['article_id', 'channel', 'created_at']);
            $table->index(['review_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
