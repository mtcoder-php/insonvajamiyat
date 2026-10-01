<?php

namespace App\Http\Requests\Cabinet\Articles;

use App\Concerns\UserProfileValidationRules;
use App\Services\Articles\ArticleSubmissionService as Submission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

/**
 * 2-bosqich: mualliflar ro'yxati va aloqa uchun mas'ul muallif.
 */
class ArticleAuthorsRequest extends DraftArticleRequest
{
    use UserProfileValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'authors' => ['required', 'array', 'min:1', 'max:'.Submission::MAX_AUTHORS],
            'authors.*.last_name' => $this->personNameRules(),
            'authors.*.first_name' => $this->personNameRules(),
            'authors.*.middle_name' => ['nullable', 'string', 'max:100'],
            'authors.*.email' => ['nullable', 'email', 'max:255', 'distinct:ignore_case'],
            'authors.*.organization' => ['required', 'string', 'max:255'],
            'authors.*.position' => ['nullable', 'string', 'max:255'],
            'authors.*.academic_degree' => ['nullable', 'string', 'max:100'],
            'authors.*.orcid' => ['nullable', 'string', 'regex:'.self::ORCID_PATTERN],
            'authors.*.is_me' => ['boolean'],
            'corresponding' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $authors = $this->authors();
                $corresponding = $this->integer('corresponding');

                if (count(array_filter($authors, fn (array $author): bool => (bool) ($author['is_me'] ?? false))) > 1) {
                    $validator->errors()->add('authors', __('Faqat bitta muallif "Men" deb belgilanishi mumkin.'));
                }

                if (! array_key_exists($corresponding, $authors)) {
                    $validator->errors()->add('corresponding', __("Aloqa uchun mas'ul muallifni belgilang."));
                } elseif (blank($authors[$corresponding]['email'] ?? null)) {
                    $validator->errors()->add(
                        "authors.{$corresponding}.email",
                        __("Aloqa uchun mas'ul muallifning elektron pochtasi majburiy."),
                    );
                }
            },
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function authors(): array
    {
        $authors = $this->input('authors', []);

        return is_array($authors) ? array_values(array_filter($authors, 'is_array')) : [];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'authors' => __('Mualliflar'),
            'authors.*.last_name' => __('Familiya'),
            'authors.*.first_name' => __('Ism'),
            'authors.*.middle_name' => __('Otasining ismi'),
            'authors.*.email' => __('Elektron pochta'),
            'authors.*.organization' => __('Tashkilot'),
            'authors.*.position' => __('Lavozim'),
            'authors.*.academic_degree' => __('Ilmiy daraja'),
            'authors.*.orcid' => 'ORCID',
            'corresponding' => __("Aloqa uchun mas'ul muallif"),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'authors.*.orcid.regex' => __('ORCID formati: 0000-0000-0000-0000'),
            'authors.*.email.distinct' => __('Mualliflarning elektron pochtalari takrorlanmasligi kerak.'),
        ];
    }
}
