<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tizim sozlamalari (key-value, guruhlangan).
 * Guruhlar: general, journal (ISSN, nomi), ai, click, payme, mail.
 *
 * is_encrypted=true bo'lgan qiymatlar (Click secret_key, Payme key, AI API kaliti)
 * Laravel Crypt (APP_KEY) bilan shifrlanib saqlanadi va faqat Super Admin ko'radi
 * (TZ 4.2.7, 4.2.8, 7). Agar qiymat bo'sh bo'lsa — .env'dagi qiymat ishlatiladi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50);
            $table->string('key', 100);
            $table->longText('value')->nullable();
            $table->string('type', 20)->default('string');    // string, integer, boolean, json
            $table->boolean('is_encrypted')->default(false);
            $table->string('description')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
