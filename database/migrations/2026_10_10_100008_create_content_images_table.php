<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Matn muharriri orqali yuklangan rasmlar (yangilik / tadbir matni ichida). Hech qaysi matnda
 * ishlatilmay qolganlari (yuklab, keyin o'chirilgan yoki saqlanmagan) har kuni tozalanadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_images', function (Blueprint $table) {
            $table->id();
            $table->string('path')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedInteger('size');
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_images');
    }
};
