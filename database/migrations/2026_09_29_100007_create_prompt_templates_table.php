<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI "system prompt" shablonlari (TZ 4.2.7, 6-bo'lim).
 * Kodni o'zgartirmasdan Super Admin tahrirlaydi.
 * key misollari: spell_check.uz, translation.uz_ru, translation.uz_en
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('name');
            $table->string('type', 30);                       // App\Enums\AiRequestType
            $table->char('source_language', 2)->default('uz');
            $table->char('target_language', 2)->nullable();   // spell_check uchun null
            $table->longText('system_prompt');
            $table->text('user_prompt_template')->nullable(); // {{text}} o'rinbosari bilan
            $table->string('model', 100)->nullable();         // null → config('ai.model')
            $table->decimal('temperature', 3, 2)->default(0.20);
            $table->unsignedInteger('max_tokens')->default(8192);
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'source_language', 'target_language', 'is_active'], 'prompt_templates_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_templates');
    }
};
