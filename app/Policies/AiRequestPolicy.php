<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\AiRequest;
use App\Models\User;

/**
 * AI so'rovi: egasi ko'radi va natija bilan ishlaydi; AI sozlamalari ruxsati borlar faqat ko'radi.
 */
class AiRequestPolicy
{
    public function view(User $user, AiRequest $request): bool
    {
        return $request->user_id === $user->id || $user->can(PermissionName::AiSettingsManage->value);
    }

    public function update(User $user, AiRequest $request): bool
    {
        return $request->user_id === $user->id;
    }
}
