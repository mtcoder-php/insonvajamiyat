<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Click/Payme'dan kelgan HAR BIR webhook so'rovi va javobi (TZ 7: "to'lov
 * sahifalari uchun qo'shimcha loglash"). Soxta so'rovlar ham yoziladi
 * (signature_valid=false) — hujumlarni tahlil qilish uchun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 20);
            $table->string('action', 50);                     // Payme: CheckPerformTransaction..., Click: prepare/complete
            $table->json('request');
            $table->json('response')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->integer('error_code')->nullable();
            $table->boolean('signature_valid')->nullable();
            $table->string('ip', 45)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['provider', 'created_at']);
            $table->index(['payment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
