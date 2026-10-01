<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;

/**
 * Foydalanuvchilarni boshqarish ruxsatlari (TZ 4.2.4).
 *
 * Super Admin Gate::before orqali hammasiga ega; quyidagi qoidalar
 * users.manage ruxsati bor boshqa xodimlar uchun: ular Bosh administrator
 * akkauntini o'zgartira olmaydi. "O'zini o'chirish/bloklash mumkin emas"
 * qoidasi hamma uchun — App\Services\Admin\UserService da.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $this->canManage($actor);
    }

    public function view(User $actor, User $user): bool
    {
        return $this->canManage($actor);
    }

    public function create(User $actor): bool
    {
        return $this->canManage($actor);
    }

    public function update(User $actor, User $user): bool
    {
        return $this->canManage($actor) && $this->canTouch($actor, $user);
    }

    public function delete(User $actor, User $user): bool
    {
        return $this->update($actor, $user);
    }

    public function restore(User $actor, User $user): bool
    {
        return $this->update($actor, $user);
    }

    public function block(User $actor, User $user): bool
    {
        return $this->update($actor, $user);
    }

    private function canManage(User $actor): bool
    {
        return $actor->can(PermissionName::UsersManage->value);
    }

    private function canTouch(User $actor, User $user): bool
    {
        return $actor->isSuperAdmin() || ! $user->isSuperAdmin();
    }
}
