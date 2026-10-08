<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mualliflar uchun yuklab olinadigan fayllar (TZ 4.2.5): maqola shabloni, yo'riqnoma,
 * ariza/shartnoma shakllari. Admin → Sozlamalar → Fayllar orqali yuklanadi va yangilanadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);                         // App\Enums\JournalDocumentKind
            $table->json('title');                              // uz/ru/en
            $table->json('description')->nullable();
            $table->string('path');                             // public disk: documents/...
            $table->string('original_name');                    // Yuklab olishdagi fayl nomi
            $table->string('extension', 10);
            $table->unsignedInteger('size');                    // bayt
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedInteger('downloads_count')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'kind', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_documents');
    }
};
