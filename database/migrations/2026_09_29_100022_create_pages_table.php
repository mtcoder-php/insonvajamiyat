<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Statik sahifalar: "Jurnal haqida", "Mualliflar uchun yo'riqnoma", "Aloqa",
 * "Nashr etikasi" va h.k. (TZ 4.2.5). Kontent uch tilda JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();                 // about, author-guidelines, contact
            $table->json('title');
            $table->json('content')->nullable();              // HTML (rich text editor)
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->boolean('show_in_menu')->default(false);
            $table->unsignedInteger('menu_order')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
