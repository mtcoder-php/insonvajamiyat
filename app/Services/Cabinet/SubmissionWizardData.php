<?php

namespace App\Services\Cabinet;

use App\Enums\Language;
use App\Enums\SubmissionStep;
use App\Http\Controllers\Cabinet\DashboardController;
use App\Http\Requests\Cabinet\Articles\ArticleFileRequest;
use App\Http\Requests\Cabinet\Articles\SubmitArticleRequest;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleFile;
use App\Models\ArticleType;
use App\Models\Subject;
use App\Models\User;
use App\Services\Articles\ArticleSubmissionService as Submission;

/**
 * "Yangi maqola yuborish" sahifasi (cabinet/articles/Wizard) uchun props.
 */
class SubmissionWizardData
{
    public function __construct(private readonly Submission $submission) {}

    /**
     * @return array<string, mixed>
     */
    public function props(User $user, ?Article $article, SubmissionStep $step): array
    {
        $checklist = $article !== null ? $this->submission->checklist($article) : null;

        return [
            'step' => $step->value,
            'steps' => SubmissionStep::options(),
            'article' => $article !== null ? $this->article($article) : null,
            'checklist' => $checklist,
            'isComplete' => $article !== null && $this->submission->isComplete($article, $checklist),
            'options' => [
                'types' => $this->types(),
                'subjects' => Subject::query()->active()->get()->map(fn (Subject $subject): array => [
                    'id' => $subject->id,
                    'name' => $subject->name,
                ])->all(),
                'languages' => array_map(fn (Language $language): array => [
                    'code' => $language->value,
                    'label' => $language->label(),
                ], Language::cases()),
            ],
            'me' => $this->submission->profileAuthor($user),
            'limits' => $this->limits(),
            'consents' => SubmitArticleRequest::CONSENTS,
            'links' => DashboardController::links(),
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, description: string|null, price: float, currency: string, reviewDays: int|null}>
     */
    private function types(): array
    {
        return ArticleType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ArticleType $type): array => [
                'id' => $type->id,
                'name' => $type->name,
                'description' => $type->description,
                'price' => (float) $type->price,
                'currency' => $type->currency,
                'reviewDays' => $type->review_days,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function article(Article $article): array
    {
        $article->loadMissing(['authors', 'files']);

        return [
            'uuid' => $article->uuid,
            'status' => $article->status->value,
            'articleTypeId' => $article->article_type_id,
            'subjectId' => $article->subject_id,
            'language' => $article->language,
            'udc' => $article->udc,
            'title' => $this->localized($article, 'title'),
            'abstract' => $this->localized($article, 'abstract'),
            'keywords' => $this->keywords($article),
            'references' => $article->references,
            'authors' => $article->authors->map(fn (ArticleAuthor $author): array => [
                'last_name' => $author->last_name,
                'first_name' => $author->first_name,
                'middle_name' => $author->middle_name,
                'email' => $author->email,
                'organization' => $author->organization,
                'position' => $author->position,
                'academic_degree' => $author->academic_degree,
                'orcid' => $author->orcid,
                'is_me' => $author->user_id === $article->submitter_id,
                'is_corresponding' => $author->is_corresponding,
            ])->all(),
            'files' => $article->files->map(fn (ArticleFile $file): array => [
                'uuid' => $file->uuid,
                'name' => $file->original_name,
                'type' => $file->type->value,
                'typeLabel' => $file->type->label(),
                'extension' => $file->extension(),
                'size' => $file->size,
                'uploadedAt' => $file->created_at?->toIso8601String(),
                'url' => route('cabinet.articles.files.download', [$article->uuid, $file->uuid]),
                'deleteUrl' => route('cabinet.articles.files.destroy', [$article->uuid, $file->uuid]),
            ])->all(),
            'updatedAt' => $article->updated_at?->toIso8601String(),
            'urls' => [
                'edit' => route('cabinet.articles.edit', $article->uuid),
                'show' => route('cabinet.articles.show', $article->uuid),
                'details' => route('cabinet.articles.details.update', $article->uuid),
                'authors' => route('cabinet.articles.authors.update', $article->uuid),
                'abstract' => route('cabinet.articles.abstract.update', $article->uuid),
                'keywords' => route('cabinet.articles.keywords.update', $article->uuid),
                'files' => route('cabinet.articles.files.store', $article->uuid),
                'submit' => route('cabinet.articles.submit', $article->uuid),
                'destroy' => route('cabinet.articles.destroy', $article->uuid),
            ],
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function localized(Article $article, string $key): array
    {
        $values = [];

        foreach (Language::cases() as $language) {
            $value = $article->getTranslation($key, $language->value, false);
            $values[$language->value] = is_string($value) && $value !== '' ? $value : null;
        }

        return $values;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function keywords(Article $article): array
    {
        $values = [];

        foreach (Language::cases() as $language) {
            $words = $article->getTranslation('keywords', $language->value, false);
            $values[$language->value] = Submission::normalizeKeywords(is_array($words) ? array_values($words) : []);
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    private function limits(): array
    {
        $files = [];

        foreach (ArticleFileRequest::TYPES as $type) {
            $files[$type->value] = [
                'label' => $type->label(),
                'extensions' => $type->allowedMimes(),
                'maxKb' => $type->maxSizeKb(),
            ];
        }

        return [
            'maxAuthors' => Submission::MAX_AUTHORS,
            'titleMin' => Submission::TITLE_MIN,
            'titleMax' => Submission::TITLE_MAX,
            'abstractMin' => Submission::ABSTRACT_MIN,
            'abstractMax' => Submission::ABSTRACT_MAX,
            'referencesMax' => Submission::REFERENCES_MAX,
            'keywordsMin' => Submission::KEYWORDS_MIN,
            'keywordsMax' => Submission::KEYWORDS_MAX,
            'keywordMaxLength' => Submission::KEYWORD_MAX_LENGTH,
            'supplementaryMax' => Submission::SUPPLEMENTARY_MAX,
            'files' => $files,
        ];
    }
}
