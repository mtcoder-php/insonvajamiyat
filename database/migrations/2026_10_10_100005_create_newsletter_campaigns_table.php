<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Obunachilarga yuborilgan xatlar (admin → Obuna). `kind`: manual — admin yozgan,
 * issue — yangi son chop etilganda avtomatik. `locale` bo'sh — barcha tillar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('kind', 10)->default('manual');      // manual | issue
            $table->string('subject', 200);
            $table->text('body');
            $table->string('button_label', 60)->nullable();
            $table->string('button_url', 500)->nullable();
            $table->char('locale', 2)->nullable();              // null — barcha obunachilar
            $table->foreignId('journal_issue_id')->nullable()->constrained('journal_issues')->nullOnDelete();
            $table->string('status', 20)->default('queued');    // queued | sending | sent | failed
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->string('error', 500)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_campaigns');
    }
};
