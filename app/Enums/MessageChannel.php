<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/**
 * Yozishma kanali. Blind review saqlanishi uchun muallif va taqrizchi
 * hech qachon bitta kanalda bo'lmaydi — muharrir vositachi.
 */
enum MessageChannel: string
{
    use EnumHelpers;

    case AuthorEditor = 'author_editor';
    case EditorReviewer = 'editor_reviewer';

    public function label(): string
    {
        return match ($this) {
            self::AuthorEditor => __('Muallif — Tahririyat'),
            self::EditorReviewer => __('Tahririyat — Taqrizchi'),
        };
    }
}
