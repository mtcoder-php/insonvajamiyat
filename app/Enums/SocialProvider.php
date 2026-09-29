<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** Socialite orqali kirish (social_accounts.provider) */
enum SocialProvider: string
{
    use EnumHelpers;

    case Google = 'google';
    case Orcid = 'orcid';

    public function label(): string
    {
        return match ($this) {
            self::Google => 'Google',
            self::Orcid => 'ORCID',
        };
    }
}
