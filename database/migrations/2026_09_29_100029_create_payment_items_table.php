<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * To'lov qatorlari. Misol — bitta maqola uchun bitta to'lov:
 *   1) Ilmiy maqola (article_type_id=1)        1 × 320 000 = 320 000
 *   2) Qo'shimcha sahifa (service extra_page)   3 ×  50 000 = 150 000
 *   3) Tezkor ko'rib chiqish (fast_track)       1 × 150 000 = 150 000
 * Har bir qatorda nom va narx snapshot — katalog o'zgarsa ham chek o'zgarmaydi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            // Aynan bittasi to'ldiriladi (Service qatlamida tekshiriladi)
            $table->foreignId('article_type_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->restrictOnDelete();

            $table->json('name');                              // Snapshot: xizmat nomi
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();

            $table->index('payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_items');
    }
};
