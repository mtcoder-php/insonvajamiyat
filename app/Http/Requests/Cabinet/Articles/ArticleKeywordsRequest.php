<?php

namespace App\Http\Requests\Cabinet\Articles;

use App\Enums\Language;
use App\Services\Articles\ArticleSubmissionService as Submission;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 4-bosqich: kalit so'zlar (maqola tilida 3–10 ta majburiy, boshqa tillarda ixtiyoriy).
 */
class ArticleKeywordsRequest extends DraftArticleRequest
{
    /**
     * Tozalangan (trim, takrorsiz) qiymatlar bilan tekshiriladi.
     */
    protected function prepareForValidation(): void
    {
        $keywords = $this->input('keywords');
        $normalized = [];

        foreach (Language::cases() as $language) {
            $words = is_array($keywords) ? ($keywords[$language->value] ?? []) : [];
            $normalized[$language->value] = Submission::normalizeKeywords(is_array($words) ? $words : []);
        }

        $this->merge(['keywords' => $normalized]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $main = $this->article()?->language;
        $rules = ['keywords' => ['required', 'array']];

        foreach (Language::cases() as $language) {
            $locale = $language->value;
            $rules["keywords.{$locale}"] = $locale === $main
                ? ['required', 'array', 'min:'.Submission::KEYWORDS_MIN, 'max:'.Submission::KEYWORDS_MAX]
                : ['nullable', 'array', 'max:'.Submission::KEYWORDS_MAX];
            $rules["keywords.{$locale}.*"] = ['string', 'max:'.Submission::KEYWORD_MAX_LENGTH];
        }

        return $rules;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function keywords(): array
    {
        $keywords = $this->input('keywords');
        $result = [];

        foreach (Language::cases() as $language) {
            $words = is_array($keywords) ? ($keywords[$language->value] ?? []) : [];
            $result[$language->value] = is_array($words) ? array_values($words) : [];
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach (Language::cases() as $language) {
            $attributes["keywords.{$language->value}"] = __("Kalit so'zlar").' ('.$language->label().')';
            $attributes["keywords.{$language->value}.*"] = __("Kalit so'z");
        }

        return $attributes;
    }
}
