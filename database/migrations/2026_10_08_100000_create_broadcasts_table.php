<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin → Xabarlar → Ommaviy xabar: foydalanuvchilar guruhiga e'lon
 * (bildirishnoma + ixtiyoriy email). Yuborish navbat orqali, holat shu yerda kuzatiladi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('subject', 200);
            $table->text('body');
            $table->string('audience', 30);                   // App\Enums\BroadcastAudience
            $table->boolean('send_email')->default(true);
            $table->string('status', 20)->default('queued');  // queued | sending | sent | failed
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->string('error', 500)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcasts');
    }
};
