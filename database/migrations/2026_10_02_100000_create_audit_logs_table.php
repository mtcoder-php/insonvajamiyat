<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit log (TZ 4.2.9): tizimdagi muhim amallar — kim, qachon, nima qildi.
 *
 * subject_label — obyekt nomining nusxasi (maqola kodi, foydalanuvchi ismi):
 * obyekt o'chirilsa ham yozuv tushunarli qoladi.
 * Faqat qo'shiladi (append-only): updated_at yo'q, yozuvlar tahrirlanmaydi.
 * Eski yozuvlar `php artisan model:prune` bilan tozalanadi (config journal.audit.retention_days).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 50);                       // App\Enums\AuditEvent
            $table->string('subject_type', 50)->nullable();    // morph alias: article, user, payment, issue, review
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();
            $table->string('description', 500)->nullable();
            $table->json('properties')->nullable();            // {old: {...}, new: {...}} yoki qo'shimcha ma'lumot
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
