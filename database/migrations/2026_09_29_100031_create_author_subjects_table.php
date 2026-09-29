<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Muallifning ilmiy yo'nalishlari (onboarding: "bir nechta tanlash mumkin").
 * Taqrizchi tanlashda ham ishlatiladi — maqola sohasiga mos taqrizchilar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('author_subjects', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['user_id', 'subject_id']);
            $table->index('subject_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_subjects');
    }
};
