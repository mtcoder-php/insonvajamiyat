<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zaxira nusxalar jurnali: har bir arxiv (baza + fayllar) holati, hajmi va davomiyligi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 20);                       // App\Enums\BackupType
            $table->string('status', 20)->default('queued');  // queued | running | done | failed
            $table->string('trigger', 20)->default('manual'); // manual | schedule
            $table->string('disk', 40);
            $table->string('path')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('files_count')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('error', 1000)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
