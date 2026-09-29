<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Taqrizchi biriktiruvi + taqriz natijasi (TZ 4.2.2).
 * Bitta maqolaga bir nechta taqrizchi, har bir taqriz raundida alohida yozuv.
 * Blind review: muallifga reviewer_id hech qachon ko'rsatilmaydi (Resource darajasida).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('round')->default(1);

            $table->string('status', 20)->default('invited');        // App\Enums\ReviewStatus
            $table->string('recommendation', 30)->nullable();        // App\Enums\ReviewRecommendation
            $table->decimal('score', 2, 1)->nullable();              // Umumiy baho 1.0–5.0 (yulduzlar)

            // Mezonlar bo'yicha baho (0.5 qadam, 1–5) — dizayndagi 6 ta slayder:
            // {"relevance":4.0,"novelty":3.5,"methodology":4.0,"results":4.0,"conclusions":3.5,"references":4.0}
            $table->json('criteria_scores')->nullable();

            $table->text('comments_to_author')->nullable();          // Muallifga ko'rinadi (anonim)
            $table->text('comments_to_editor')->nullable();          // Faqat tahririyat uchun
            $table->string('attachment_path')->nullable();           // Taqriz fayli (ixtiyoriy)

            $table->timestamp('due_at')->nullable();
            $table->timestamp('responded_at')->nullable();           // Qabul/rad qilgan vaqti
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['article_id', 'reviewer_id', 'round']);
            $table->index(['reviewer_id', 'status']);
            $table->index(['article_id', 'round']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
