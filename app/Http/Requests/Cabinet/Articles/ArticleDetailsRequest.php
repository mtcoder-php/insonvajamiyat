<?php

namespace App\Http\Requests\Cabinet\Articles;

use App\Enums\Language;
use App\Services\Articles\ArticleSubmissionService as Submission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * 1-bosqich: maqola turi, yo'nalish, til, sarlavha, UDK.
 */
class ArticleDetailsRequest extends DraftArticleRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'article_type_id' => [
                'required', 'integer',
                Rule::exists('article_types', 'id')->where('is_active', true)->whereNull('deleted_at'),
            ],
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')->where('is_active', true)],
            'language' => ['required', Rule::enum(Language::class)],
            'title' => ['required', 'string', 'min:'.Submission::TITLE_MIN, 'max:'.Submission::TITLE_MAX],
            'udc' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array{article_type_id: int, subject_id: int, language: string, title: string, udc: string|null}
     */
    public function details(): array
    {
        return [
            'article_type_id' => $this->integer('article_type_id'),
            'subject_id' => $this->integer('subject_id'),
            'language' => $this->string('language')->toString(),
            'title' => $this->string('title')->squish()->toString(),
            'udc' => $this->filled('udc') ? $this->string('udc')->trim()->toString() : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'article_type_id' => __('Maqola turi'),
            'subject_id' => __("Ilmiy yo'nalish"),
            'language' => __('Maqola tili'),
            'title' => __('Sarlavha'),
            'udc' => __('UDK'),
        ];
    }
}
