<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Hamkorlarimiz" karuseli va indekslash bazalari (Google Scholar, CrossRef ...).
 * Bosh sahifadagi "8 Xalqaro indekslar" soni type=indexing bo'yicha hisoblanadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);                       // App\Enums\PartnerType
            $table->json('name');
            $table->json('subtitle')->nullable();             // Hero kartasidagi izoh: "Indekslangan", "Hamkorlik"
            $table->string('logo_path')->nullable();
            $table->string('url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
