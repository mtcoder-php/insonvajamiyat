<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Kutubxona: "Tavsiya etilgan kitoblar" jadvali to'liq kutubxonaga aylanadi (books).
 * Mavjud kitoblar saqlanadi va "tavsiya etilgan" (is_featured) deb belgilanadi —
 * bosh sahifadagi blok avvalgidek ko'rinadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('recommended_books', 'books');

        Schema::table('books', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('id');
            $table->json('description')->nullable()->after('title');     // {"uz": "...", "ru": "...", "en": "..."}
            $table->foreignId('subject_id')->nullable()->after('description')->constrained('subjects')->nullOnDelete();
            $table->string('publisher')->nullable()->after('author');    // nashriyot
            $table->unsignedSmallInteger('pages')->nullable()->after('year');
            $table->string('isbn', 20)->nullable()->after('pages');
            $table->string('language', 5)->default('uz')->after('isbn'); // uz | ru | en
            $table->string('file_path')->nullable()->after('cover_image_path');   // PDF (local disk, maxfiy)
            $table->string('file_name')->nullable()->after('file_path');          // asl nomi
            $table->unsignedBigInteger('file_size')->nullable()->after('file_name');
            $table->boolean('is_featured')->default(false)->after('sort_order');  // bosh sahifada "Tavsiya etilgan"
            $table->unsignedInteger('views_count')->default(0)->after('is_active');
            $table->unsignedInteger('downloads_count')->default(0)->after('views_count');

            $table->index(['is_active', 'is_featured']);
        });

        // Mavjud kitoblar: slug va "tavsiya etilgan" belgisi
        $used = [];

        foreach (DB::table('books')->orderBy('id')->get(['id', 'title']) as $book) {
            $titles = json_decode((string) $book->title, true);
            $title = is_array($titles) ? (string) ($titles['uz'] ?? reset($titles) ?: '') : '';
            $base = rtrim(Str::substr(Str::slug(Str::ascii($title)), 0, 80), '-') ?: 'kitob';
            $slug = $base;
            $i = 2;

            while (isset($used[$slug])) {
                $slug = $base.'-'.$i++;
            }

            $used[$slug] = true;
            DB::table('books')->where('id', $book->id)->update(['slug' => $slug, 'is_featured' => true]);
        }

        Schema::table('books', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropIndex(['is_active', 'is_featured']);
            $table->dropConstrainedForeignId('subject_id');
            $table->dropColumn([
                'slug', 'description', 'publisher', 'pages', 'isbn', 'language',
                'file_path', 'file_name', 'file_size', 'is_featured', 'views_count', 'downloads_count',
            ]);
        });

        Schema::rename('books', 'recommended_books');
    }
};
