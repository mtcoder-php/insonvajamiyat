<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tarjima versiyalari: v1 — AI natijasi, v2.. — muallifning inline tahrirlari.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('translation_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->longText('content');
            $table->boolean('is_ai_generated')->default(false);
            $table->foreignId('ai_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['translation_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_versions');
    }
};
