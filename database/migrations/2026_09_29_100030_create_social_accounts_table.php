<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Google / ORCID orqali kirish (Laravel Socialite).
 * Bitta user bir nechta provayderni bog'lashi mumkin.
 * ORCID orqali kirilganda author_profiles.orcid avtomatik to'ldiriladi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 20);                   // App\Enums\SocialProvider
            $table->string('provider_user_id', 191);
            $table->string('email')->nullable();
            $table->text('access_token')->nullable();         // Model'da 'encrypted' cast
            $table->text('refresh_token')->nullable();        // Model'da 'encrypted' cast
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_user_id']);
            $table->unique(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
