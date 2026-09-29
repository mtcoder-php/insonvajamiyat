<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** "Tahririyat kengashi" sahifasidagi a'zo roli (web ko'rinish uchun, RBAC emas) */
enum EditorialBoardRole: string
{
    use EnumHelpers;

    case ChiefEditor = 'chief_editor';
    case DeputyChiefEditor = 'deputy_chief_editor';
    case ExecutiveSecretary = 'executive_secretary';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::ChiefEditor => __('Bosh muharrir'),
            self::DeputyChiefEditor => __("Bosh muharrir o'rinbosari"),
            self::ExecutiveSecretary => __("Mas'ul kotib"),
            self::Member => __('Tahririyat kengashi a\'zosi'),
        };
    }
}
