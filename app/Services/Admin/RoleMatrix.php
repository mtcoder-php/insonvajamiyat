<?php

namespace App\Services\Admin;

use App\Enums\AuditEvent;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Admin → Rollar va ruxsatlar: rol × ruxsat matritsasi.
 *
 * Qoidalar:
 *   - Bosh administrator o'zgartirilmaydi (Gate::before orqali hamma ruxsatga ega);
 *   - xodim rollarida "Admin panelga kirish" doim yoqilgan (aks holda rol ma'nosiz);
 *   - Muallif roliga faqat mualliflar uchun ruxsatlar (AI Studio) berilishi mumkin;
 *   - Bosh administrator bo'lmagan foydalanuvchi o'z rolidan "Rollar va ruxsatlarni
 *     boshqarish" ruxsatini olib tashlay olmaydi (o'zini bo'limdan chiqarib qo'ymaslik uchun).
 */
class RoleMatrix
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly PermissionRegistrar $registrar,
    ) {}

    /**
     * @return array{roles: array<int, array<string, mixed>>, groups: array<int, array{key: string, label: string, permissions: array<int, array{value: string, label: string, forAuthors: bool}>}>}
     */
    public function matrix(): array
    {
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions:id,name')
            ->withCount('users')
            ->get()
            ->keyBy('name');

        $rows = [];

        foreach (RoleName::cases() as $name) {
            $role = $roles->get($name->value);
            $current = $role instanceof Role
                ? $role->permissions->pluck('name')->map(fn (mixed $n): string => (string) $n)->sort()->values()->all()
                : [];
            $defaults = collect(PermissionName::forRole($name))->map(fn (PermissionName $p): string => $p->value)->sort()->values()->all();

            $rows[] = [
                'name' => $name->value,
                'label' => $name->label(),
                'isStaff' => $name->isStaff(),
                'locked' => $name === RoleName::SuperAdmin,
                'users' => $role instanceof Role ? (int) $role->getAttribute('users_count') : 0,
                'permissions' => $name === RoleName::SuperAdmin ? PermissionName::values() : $current,
                'defaults' => $defaults,
                'isDefault' => $name === RoleName::SuperAdmin || $current === $defaults,
                'usersUrl' => route('admin.users.index', ['role' => $name->value]),
                'updateUrl' => $name === RoleName::SuperAdmin ? null : route('admin.roles.update', $name->value),
                'resetUrl' => $name === RoleName::SuperAdmin ? null : route('admin.roles.reset', $name->value),
            ];
        }

        $groups = [];

        foreach (PermissionName::groups() as $key => $label) {
            $groups[] = [
                'key' => $key,
                'label' => $label,
                'permissions' => array_values(array_map(
                    fn (PermissionName $p): array => ['value' => $p->value, 'label' => $p->label(), 'forAuthors' => $p->forAuthors()],
                    array_filter(PermissionName::cases(), fn (PermissionName $p): bool => $p->group() === $key),
                )),
            ];
        }

        return ['roles' => $rows, 'groups' => $groups];
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function update(RoleName $name, array $permissions, User $actor): void
    {
        $this->sync($name, $this->normalize($name, $permissions, $actor), $actor, reset: false);
    }

    public function reset(RoleName $name, User $actor): void
    {
        $defaults = array_map(fn (PermissionName $p): string => $p->value, PermissionName::forRole($name));

        $this->sync($name, $this->normalize($name, $defaults, $actor), $actor, reset: true);
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    private function normalize(RoleName $name, array $permissions, User $actor): array
    {
        if ($name === RoleName::SuperAdmin) {
            throw ValidationException::withMessages([
                'permissions' => __("Bosh administrator ruxsatlarini o'zgartirib bo'lmaydi — u barcha ruxsatlarga ega."),
            ]);
        }

        $selected = array_values(array_filter(
            PermissionName::cases(),
            fn (PermissionName $p): bool => in_array($p->value, $permissions, true),
        ));

        if (! $name->isStaff()) {
            $forbidden = array_filter($selected, fn (PermissionName $p): bool => ! $p->forAuthors());

            if ($forbidden !== []) {
                throw ValidationException::withMessages([
                    'permissions' => __("Muallif roliga admin panel ruxsatlarini berib bo'lmaydi. Xodim kerak bo'lsa, foydalanuvchiga tegishli rolni bering."),
                ]);
            }
        } elseif (! in_array(PermissionName::AdminAccess, $selected, true)) {
            $selected[] = PermissionName::AdminAccess;
        }

        if (! $actor->isSuperAdmin()
            && $actor->hasRole($name->value)
            && ! in_array(PermissionName::RolesManage, $selected, true)
            && $actor->can(PermissionName::RolesManage->value)
            && ! $this->otherRoleGrants($actor, $name, PermissionName::RolesManage)) {
            throw ValidationException::withMessages([
                'permissions' => __("O'z rolingizdan «Rollar va ruxsatlarni boshqarish» ruxsatini olib tashlay olmaysiz."),
            ]);
        }

        return array_map(fn (PermissionName $p): string => $p->value, $selected);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function sync(RoleName $name, array $permissions, User $actor, bool $reset): void
    {
        $role = Role::findOrCreate($name->value, 'web');
        $before = $role->permissions()->pluck('name')->map(fn (mixed $n): string => (string) $n)->all();

        DB::transaction(fn () => $role->syncPermissions($permissions));
        $this->registrar->forgetCachedPermissions();

        $added = array_values(array_diff($permissions, $before));
        $removed = array_values(array_diff($before, $permissions));

        if ($added === [] && $removed === []) {
            return;
        }

        $description = __(':role roli ruxsatlari', ['role' => $name->label()]);

        $this->audit->log(AuditEvent::RolePermissionsUpdated, null, [
            'role' => $name->value,
            'added' => $added,
            'removed' => $removed,
            'reset' => $reset,
        ], is_string($description) ? $description : null, $actor);
    }

    private function otherRoleGrants(User $user, RoleName $except, PermissionName $permission): bool
    {
        return Role::query()
            ->whereIn('name', $user->getRoleNames()->reject(fn (string $n): bool => $n === $except->value)->all())
            ->whereHas('permissions', fn ($q) => $q->where('name', $permission->value))
            ->exists();
    }
}
