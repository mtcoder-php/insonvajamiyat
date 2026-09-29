<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/**
 * Spatie Permission rollari (TZ 3.2).
 * Kodda rol nomini satr sifatida yozmaslik uchun: $user->hasRole(RoleName::Editor)
 */
enum RoleName: string
{
    use EnumHelpers;

    // Admin panel
    case SuperAdmin = 'super_admin';
    case ChiefEditor = 'chief_editor';
    case Editor = 'editor';
    case Reviewer = 'reviewer';
    case LayoutEditor = 'layout_editor';
    case ContentManager = 'content_manager';

    // Web qism
    case Author = 'author';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => __('Bosh administrator'),
            self::ChiefEditor => __('Bosh muharrir'),
            self::Editor => __('Muharrir'),
            self::Reviewer => __('Taqrizchi'),
            self::LayoutEditor => __('Texnik xodim (maketchi)'),
            self::ContentManager => __('Kontent-menejer'),
            self::Author => __('Muallif'),
        };
    }

    /**
     * Admin panelga kira oladigan rollar
     *
     * @return array<int, self>
     */
    public static function staff(): array
    {
        return [
            self::SuperAdmin,
            self::ChiefEditor,
            self::Editor,
            self::Reviewer,
            self::LayoutEditor,
            self::ContentManager,
        ];
    }

    /** @return array<int, string> */
    public static function staffValues(): array
    {
        return array_map(fn (self $r) => $r->value, self::staff());
    }

    public function isStaff(): bool
    {
        return $this !== self::Author;
    }
}
