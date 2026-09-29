<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pullik xizmatlar katalogi (dizayn: "Xizmatni tanlang").
 * Maqola nashri narxi article_types'da qoladi; bu jadval — tezkor ko'rib chiqish,
 * qo'shimcha sahifa, tarjima, AI tekshiruv, jurnal to'plami (PDF).
 * Narx o'zgarsa eski to'lovlar buzilmaydi — payment_items snapshot saqlaydi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();             // App\Enums\ServiceCode
            $table->json('name');
            $table->json('description')->nullable();
            $table->decimal('price', 12, 2);                  // so'm (per_unit bo'lsa — 1 birlik narxi)
            $table->char('currency', 3)->default('UZS');
            $table->json('unit_label')->nullable();           // {"uz":"sahifa","ru":"страница","en":"page"}
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
