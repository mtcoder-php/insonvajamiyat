<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Tahririyat kengashi" sahifasi a'zolari (TZ 4.1.1, 4.2.5).
 * Tashqi olimlar ham bo'lishi mumkin → user_id nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editorial_board_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('full_name');
            $table->string('role', 30)->default('member');    // App\Enums\EditorialBoardRole
            $table->json('position')->nullable();
            $table->json('organization')->nullable();
            $table->json('academic_degree')->nullable();
            $table->char('country', 2)->nullable();
            $table->string('email')->nullable();
            $table->string('orcid', 19)->nullable();
            $table->string('photo_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'role', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editorial_board_members');
    }
};
