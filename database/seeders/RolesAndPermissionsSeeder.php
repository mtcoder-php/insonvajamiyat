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
 * Idempotent: qayta ishga tushirilsa ruxsatlar sinxronlanadi, dublikat yaratilmaydi.
 *
 *   php artisan db:seed --class=RolesAndPermissionsSeeder
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionName::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        foreach (RoleName::cases() as $roleName) {
            $role = Role::findOrCreate($roleName->value, 'web');

            $role->syncPermissions(
                array_map(fn (PermissionName $p) => $p->value, PermissionName::forRole($roleName))
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
