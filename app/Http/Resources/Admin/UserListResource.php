<?php

namespace App\Http\Resources\Admin;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin panel — foydalanuvchilar jadvali qatori.
 *
 * @mixin User
 */
class UserListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatarUrl' => $this->avatarUrl(),
            'organization' => $this->authorProfile?->organization,
            'roles' => self::roles($this->resource),
            'isBlocked' => $this->is_blocked,
            'isVerified' => $this->email_verified_at !== null,
            'isDeleted' => $this->deleted_at !== null,
            'lastLoginAt' => $this->last_login_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function roles(User $user): array
    {
        $roles = [];

        foreach ($user->getRoleNames() as $name) {
            $role = RoleName::tryFrom($name);

            if ($role !== null) {
                $roles[] = ['value' => $role->value, 'label' => $role->label()];
            }
        }

        return $roles;
    }
}
