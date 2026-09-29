<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TZ'dagi "authors_profile" — Laravel nomlash konvensiyasiga ko'ra author_profiles.
 * users bilan 1:1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('author_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('last_name', 100);
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();

            $table->string('position')->nullable();          // Lavozim
            $table->string('organization')->nullable();      // Tashkilot
            $table->string('department')->nullable();        // Kafedra/bo'lim
            $table->string('academic_degree', 100)->nullable(); // Ilmiy daraja (PhD, DSc ...)
            $table->string('academic_title', 100)->nullable();  // Ilmiy unvon (dotsent, professor)
            $table->unsignedTinyInteger('experience_years')->nullable(); // Onboarding: "Tajriba yili"

            $table->string('orcid', 19)->nullable()->unique(); // 0000-0000-0000-0000
            $table->char('country', 2)->nullable()->default('UZ'); // ISO 3166-1 alpha-2
            $table->string('city', 100)->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_path')->nullable();

            // "Mualliflar" ommaviy sahifasida ko'rinishi (muallif o'zi boshqaradi)
            $table->boolean('is_public')->default(true);
            // Onboarding (Asosiy → Tashkilot → Ilmiy yo'nalish → Tayyor) yakunlangan vaqt
            $table->timestamp('onboarding_completed_at')->nullable();

            $table->timestamps();

            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_profiles');
    }
};
