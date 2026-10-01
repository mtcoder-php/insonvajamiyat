<?php

namespace App\Http\Requests\Cabinet\Articles;

use App\Enums\ArticleFileType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * 5-bosqich: asosiy fayl (.docx/.pdf) yoki qo'shimcha fayl (rasm, jadval, ilova).
 */
class ArticleFileRequest extends DraftArticleRequest
{
    /** Muallif yuklay oladigan fayl turlari */
    public const TYPES = [ArticleFileType::Manuscript, ArticleFileType::Supplementary];

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = $this->fileType() ?? ArticleFileType::Manuscript;

        return [
            'type' => ['required', Rule::in(array_map(fn (ArticleFileType $t): string => $t->value, self::TYPES))],
            'file' => [
                'required', 'file',
                'mimes:'.implode(',', $type->allowedMimes()),
                'max:'.$type->maxSizeKb(),
            ],
        ];
    }

    public function fileType(): ?ArticleFileType
    {
        $type = ArticleFileType::tryFrom($this->string('type')->toString());

        return in_array($type, self::TYPES, true) ? $type : null;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['type' => __('Fayl turi'), 'file' => __('Fayl')];
    }
}
