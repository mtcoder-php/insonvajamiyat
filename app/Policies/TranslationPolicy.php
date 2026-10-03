<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Translation;
use App\Models\User;

class TranslationPolicy
{
    public function view(User $user, Translation $translation): bool
    {
        return $translation->user_id === $user->id || $user->can(PermissionName::AiSettingsManage->value);
    }

    public function update(User $user, Translation $translation): bool
    {
        return $translation->user_id === $user->id;
    }
}
