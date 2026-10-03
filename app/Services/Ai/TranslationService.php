<?php

namespace App\Services\Ai;

use App\Models\Translation;
use App\Models\TranslationVersion;
use App\Models\User;
use App\Support\DocxWriter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Tarjima hujjati: foydalanuvchining inline tahrirlari yangi versiya bo'lib saqlanadi,
 * istalgan versiyani Word (.docx) sifatida yuklab olish mumkin.
 */
class TranslationService
{
    public const MAX_VERSIONS = 50;

    public function addVersion(Translation $translation, User $user, string $content): TranslationVersion
    {
        $content = AiStudioService::normalize($content);

        if ($content === '') {
            throw ValidationException::withMessages(['content' => __("Tarjima matni bo'sh bo'lishi mumkin emas.")]);
        }

        $latest = $translation->latestVersion;

        if ($latest !== null && $latest->content === $content) {
            return $latest;
        }

        if ($translation->versions()->count() >= self::MAX_VERSIONS) {
            throw ValidationException::withMessages(['content' => __('Versiyalar soni chegarasiga yetildi (:max).', ['max' => self::MAX_VERSIONS])]);
        }

        $version = $translation->versions()->create([
            'version' => ($latest->version ?? 0) + 1,
            'content' => $content,
            'is_ai_generated' => false,
            'created_by' => $user->id,
        ]);

        $translation->touch();

        return $version;
    }

    public function download(Translation $translation, ?int $versionNumber = null): BinaryFileResponse
    {
        $version = $versionNumber !== null
            ? $translation->versions()->where('version', $versionNumber)->firstOrFail()
            : $translation->latestVersion;

        abort_if($version === null, 404);

        $subtitle = PromptLibrary::languageLabel($translation->source_language).' → '
            .PromptLibrary::languageLabel($translation->target_language)
            .' · v'.$version->version;

        $path = DocxWriter::write($translation->title, $version->content, $subtitle);
        $name = Str::slug(Str::ascii(Str::limit($translation->title, 60, ''))) ?: 'tarjima';

        return response()
            ->download($path, "{$name}-{$translation->target_language}-v{$version->version}.docx", [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])
            ->deleteFileAfterSend();
    }
}
