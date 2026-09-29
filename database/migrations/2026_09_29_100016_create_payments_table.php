<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * To'lov tranzaksiyalari (TZ 4.1.4, 4.2.8) — sarlavha (header).
 * Nimaga to'langani payment_items'da (maqola turi + addonlar yoki xizmat).
 *
 * Oqim: muallif "To'lash" bosadi → payments'da pending yozuv + payment_items yaratiladi →
 * Click merchant_trans_id / Payme account[payment_id] = payments.id →
 * webhook shu yozuvni topadi va holatini yangilaydi.
 *
 * Idempotency (TZ talabi):
 *  1) (provider, provider_transaction_id) unique — bitta tashqi tranzaksiya = bitta yozuv
 *  2) paid_publication_key — STORED generated ustun: faqat status='paid' VA
 *     purpose='publication' bo'lganda article_id, aks holda NULL. Unique indeks bitta
 *     maqolaga ikkinchi muvaffaqiyatli nashr to'lovini DB darajasida taqiqlaydi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            $table->string('purpose', 30);                    // App\Enums\PaymentPurpose
            // To'lov obyekti (purpose'ga qarab bittasi to'ldiriladi yoki ikkalasi null — mustaqil xizmat)
            $table->foreignId('article_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('journal_issue_id')->nullable()->constrained()->restrictOnDelete();

            $table->decimal('amount', 12, 2);                 // so'm, payment_items.total yig'indisi
            $table->char('currency', 3)->default('UZS');
            $table->string('provider', 20);                   // App\Enums\PaymentProvider
            $table->string('status', 20)->default('pending'); // App\Enums\PaymentStatus

            // Tashqi tizim identifikatorlari
            $table->string('provider_transaction_id', 191)->nullable(); // Click: click_trans_id, Payme: id
            $table->string('provider_paydoc_id', 191)->nullable();      // Click: click_paydoc_id

            // Payme protokoli talab qiladigan maydonlar (ms, unix)
            $table->tinyInteger('provider_state')->nullable();          // 1, 2, -1, -2
            $table->unsignedBigInteger('provider_create_time')->nullable();
            $table->unsignedBigInteger('provider_perform_time')->nullable();
            $table->unsignedBigInteger('provider_cancel_time')->nullable();
            $table->tinyInteger('cancel_reason')->nullable();

            $table->string('receipt_number', 50)->nullable()->unique(); // Elektron chek raqami (PAY-1247)
            $table->json('meta')->nullable();

            // Qo'lda tasdiqlash (bank orqali)
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('confirmation_note')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->unsignedBigInteger('paid_publication_key')
                ->nullable()
                ->storedAs("CASE WHEN `status` = 'paid' AND `purpose` = 'publication' THEN `article_id` ELSE NULL END");

            $table->unique(['provider', 'provider_transaction_id'], 'payments_provider_txn_unique');
            $table->unique('paid_publication_key', 'payments_one_paid_publication_unique');
            $table->index(['status', 'created_at']);
            $table->index(['provider', 'status']);
            $table->index(['purpose', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
