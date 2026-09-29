<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Maqola turlari va narxlari (TZ 4.2.8).
 * Narx o'zgarganda eski to'lovlar buzilmaydi — payments.amount o'sha paytdagi
 * narxning snapshot'ini saqlaydi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_types', function (Blueprint $table) {
            $table->id();
            $table->json('name');                         // {"uz":"Ilmiy maqola", ...}
            $table->string('slug')->unique();             // scientific_article, thesis, review, express
            $table->json('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);  // so'm; 0 = bepul (to'lovsiz navbatga)
            $table->char('currency', 3)->default('UZS');
            $table->unsignedSmallInteger('review_days')->nullable(); // taxminiy ko'rib chiqish muddati
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_types');
    }
};
