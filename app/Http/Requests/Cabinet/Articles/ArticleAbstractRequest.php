<?php

namespace App\Http\Requests\Cabinet\Articles;

use App\Enums\Language;
use App\Services\Articles\ArticleSubmissionService as Submission;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 3-bosqich: annotatsiya (maqola tilida majburiy, qolgan tillarda ixtiyoriy),
 * boshqa tillardagi sarlavha va adabiyotlar ro'yxati.
 */
class ArticleAbstractRequest extends DraftArticleRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $main = $this->article()?->language;
        $rules = [
            'title' => ['nullable', 'array'],
            'abstract' => ['required', 'array'],
            'references' => ['nullable', 'string', 'max:'.Submission::REFERENCES_MAX],
        ];

        foreach (Language::cases() as $language) {
            $locale = $language->value;
            $rules["title.{$locale}"] = ['nullable', 'string', 'max:'.Submission::TITLE_MAX];
            $rules["abstract.{$locale}"] = $locale === $main
                ? ['required', 'string', 'min:'.Submission::ABSTRACT_MIN, 'max:'.Submission::ABSTRACT_MAX]
                : ['nullable', 'string', 'max:'.Submission::ABSTRACT_MAX];
        }

        return $rules;
    }

    /**
     * @return array<string, string|null>
     */
    public function titles(): array
    {
        return $this->localized('title');
    }

    /**
     * @return array<string, string|null>
     */
    public function abstracts(): array
    {
        return $this->localized('abstract');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = ['references' => __("Adabiyotlar ro'yxati")];

        foreach (Language::cases() as $language) {
            $attributes["title.{$language->value}"] = __('Sarlavha').' ('.$language->label().')';
            $attributes["abstract.{$language->value}"] = __('Annotatsiya').' ('.$language->label().')';
        }

        return $attributes;
    }

    /**
     * @return array<string, string|null>
     */
    private function localized(string $key): array
    {
        $values = [];

        foreach (Language::cases() as $language) {
            $value = $this->input("{$key}.{$language->value}");
            $values[$language->value] = is_string($value) ? $value : null;
        }

        return $values;
    }
}
