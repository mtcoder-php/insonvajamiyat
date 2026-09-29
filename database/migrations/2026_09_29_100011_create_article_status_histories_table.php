<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Status o'zgarishlari tarixi — muallifga "holat kuzatish" timeline'i
 * va admin uchun audit. Faqat qo'shiladi, tahrirlanmaydi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete(); // null = tizim (webhook, cron)
            $table->text('comment')->nullable();
            $table->boolean('is_visible_to_author')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['article_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_status_histories');
    }
};
