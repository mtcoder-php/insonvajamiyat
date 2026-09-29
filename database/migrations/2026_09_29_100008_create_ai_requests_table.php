<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI so'rovlari logi (TZ 6-bo'lim): xarajat kuzatuvi, xato tahlili, progress.
 * Katta matn bo'laklarga bo'linadi → chunks_total / chunks_completed progress-bar uchun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();                   // Frontend polling/broadcast uchun
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('prompt_template_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type', 30);                       // App\Enums\AiRequestType
            $table->string('status', 20)->default('queued');  // App\Enums\AiRequestStatus
            $table->string('provider', 30)->default('anthropic');
            $table->string('model', 100)->nullable();

            $table->char('source_language', 2)->default('uz');
            $table->char('target_language', 2)->nullable();

            $table->longText('input_text');
            $table->longText('output_text')->nullable();      // Tuzatilgan / tarjima qilingan matn
            $table->json('result')->nullable();               // Imlo takliflari: [{original, suggestion, type, reason, offset}]

            $table->unsignedSmallInteger('chunks_total')->default(1);
            $table->unsignedSmallInteger('chunks_completed')->default(0);

            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('cost_usd', 10, 6)->default(0);

            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_requests');
    }
};
