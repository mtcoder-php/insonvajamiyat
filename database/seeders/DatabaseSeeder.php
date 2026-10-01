<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Production'da faqat rollar seed qilinadi; Super Admin
     * `php artisan app:create-super-admin` bilan yaratiladi.
     */
    public function run(): void
    {
        $this->call([RolesAndPermissionsSeeder::class, SubjectSeeder::class, ArticleTypeSeeder::class]);

        if (! app()->isLocal()) {
            return;
        }

        // Lokal ishlab chiqish uchun demo hisoblar (parol: password)
        User::factory()->withRole(RoleName::SuperAdmin)->create([
            'name' => 'Super Admin',
            'email' => 'admin@insonvajamiyat.test',
        ]);

        User::factory()->withRole(RoleName::Editor)->create([
            'name' => 'Muharrir',
            'email' => 'editor@insonvajamiyat.test',
        ]);

        User::factory()->withRole(RoleName::Reviewer)->create([
            'name' => 'Taqrizchi',
            'email' => 'reviewer@insonvajamiyat.test',
        ]);

        User::factory()->author()->create([
            'name' => 'Muallif',
            'email' => 'author@insonvajamiyat.test',
        ]);

        // Bosh sahifa va katalog uchun namunaviy kontent
        $this->call(DemoContentSeeder::class);
    }
}
