<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Maqolalar — tizimning markaziy jadvali.
 *
 * Tarjimali maydonlar (title, abstract, keywords) JSON: {"uz":..,"ru":..,"en":..}
 * — spatie/laravel-translatable bilan ishlatiladi.
 * Hammualliflar — article_authors, fayllar — article_files, versiyalar — article_versions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();                  // Tashqi havolalar uchun (ID ni oshkor qilmaslik)

            $table->foreignId('submitter_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('article_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('handling_editor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->char('language', 2)->default('uz');      // Maqola yozilgan asosiy til
            $table->json('title');
            $table->json('abstract')->nullable();            // Annotatsiya (uch tilda — ixtiyoriy)
            $table->json('keywords')->nullable();            // {"uz":["..",".."], "ru":[..], "en":[..]}
            $table->string('udc', 50)->nullable();           // UDK
            $table->longText('references')->nullable();      // Adabiyotlar ro'yxati (matn)

            // Nashrdan keyin "Onlayn o'qish" uchun to'liq matn (HTML, h2/h3 → Mundarija avtomatik)
            $table->longText('body_html')->nullable();
            $table->string('cover_image_path')->nullable();  // Katalog kartochkasi rasmi
            $table->unsignedSmallInteger('pages_count')->nullable();
            $table->boolean('is_fast_track')->default(false); // Tezkor ko'rib chiqish to'langan

            // Katalog qidiruvi uchun denormallashgan matn (Observer yangilaydi)
            $table->longText('search_text')->nullable();

            $table->string('status', 30)->default('draft');           // App\Enums\ArticleStatus
            $table->string('payment_status', 20)->default('unpaid');  // App\Enums\ArticlePaymentStatus
            $table->unsignedTinyInteger('review_round')->default(0);
            $table->boolean('is_blind_review')->default(true);

            // Nashrdan keyingi ma'lumotlar
            $table->string('slug')->nullable()->unique();
            $table->string('doi')->nullable()->unique();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('downloads_count')->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0);        // "Maqolani baholash" — article_ratings'dan
            $table->unsignedInteger('ratings_count')->default(0);

            // Nashr jarayoni (Publisher sahifasi)
            $table->decimal('plagiarism_percent', 5, 2)->nullable();
            // Nashr oldidan tekshiruv: {"format":true,"plagiarism":true,"metadata":true,"doi":true,"author_consent":true}
            $table->json('production_checklist')->nullable();
            $table->foreignId('layout_editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('chief_editor_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('chief_editor_approved_at')->nullable();

            // Hayotiy sikl vaqt belgilari
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index(['submitter_id', 'status']);
            $table->index(['handling_editor_id', 'status']);
            $table->index('payment_status');
            $table->index('published_at');
        });

        // FULLTEXT faqat MySQL/MariaDB'da (testlardagi SQLite qo'llamaydi)
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('articles', function (Blueprint $table) {
                $table->fullText('search_text');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
