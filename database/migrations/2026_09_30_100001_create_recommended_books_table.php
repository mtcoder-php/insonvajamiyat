<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bosh sahifadagi "Tavsiya etilgan kitoblar" (Content Manager boshqaradi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommended_books', function (Blueprint $table) {
            $table->id();
            $table->json('title');                             // {"uz":"...", "ru":"...", "en":"..."}
            $table->string('author');                          // "A. Karimov"
            $table->unsignedSmallInteger('year')->nullable();  // nashr yili
            $table->string('cover_image_path')->nullable();
            $table->string('url')->nullable();                 // kitob sahifasi yoki PDF havola
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommended_books');
    }
};
