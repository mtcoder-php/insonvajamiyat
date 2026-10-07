<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * TZ 3.2 dagi 7 ta rol va ularning ruxsatlari.
 * Idempotent va admin o'zgartirishlarini saqlaydi:
 *   - yangi rol → standart ruxsatlar (PermissionName::forRole) beriladi;
 *   - mavjud rol → faqat shu ishga tushirishda YANGI yaratilgan ruxsatlar standart bo'yicha qo'shiladi,
 *     admin paneldagi "Rollar va ruxsatlar" bo'limida qilingan o'zgarishlar tegilmaydi.
 * Rolni standartga qaytarish — admin panelda ("Standartga qaytarish").
 *
 *   php artisan db:seed --class=RolesAndPermissionsSeeder
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);

        $registrar->forgetCachedPermissions();

        $existing = Permission::query()->where('guard_name', 'web')->pluck('name')->all();
        $created = [];

        foreach (PermissionName::cases() as $permission) {
            if (! in_array($permission->value, $existing, true)) {
                $created[] = $permission->value;
            }

            Permission::findOrCreate($permission->value, 'web');
        }

        // Spatie keshni `saved` model hodisasida tozalaydi. DatabaseSeeder'dagi
        // WithoutModelEvents hodisalarni o'chiradi — shuning uchun keshni qo'lda
        // tozalaymiz, aks holda yangi ruxsatlar "ko'rinmaydi".
        $registrar->forgetCachedPermissions();

        foreach (RoleName::cases() as $roleName) {
            $defaults = array_map(fn (PermissionName $p): string => $p->value, PermissionName::forRole($roleName));
            $role = Role::query()->where('name', $roleName->value)->where('guard_name', 'web')->first();

            if ($role === null) {
                Role::findOrCreate($roleName->value, 'web')->syncPermissions($defaults);

                continue;
            }

            $additions = array_values(array_intersect($defaults, $created));

            if ($additions !== []) {
                $role->givePermissionTo($additions);
            }
        }

        $registrar->forgetCachedPermissions();
    }
}
