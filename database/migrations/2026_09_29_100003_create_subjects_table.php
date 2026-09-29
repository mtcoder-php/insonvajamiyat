<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ilmiy sohalar/yo'nalishlar (katalogda filtr, TZ 4.1.1).
 * Ierarxik: soha → yo'nalish. name — tarjimali JSON {"uz":..,"ru":..,"en":..}
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->json('name');
            $table->string('slug')->unique();
            $table->string('code', 30)->nullable()->index(); // OAK ixtisoslik shifri, ixtiyoriy
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
