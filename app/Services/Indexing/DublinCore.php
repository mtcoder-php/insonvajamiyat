<?php

namespace App\Services\Indexing;

use App\Enums\Language;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\JournalIssue;
use App\Models\Subject;

/**
 * Maqola → Dublin Core (oai_dc) elementlari.
 *
 * Har bir element: [nom, qiymat, xml:lang|null]. Tarjimali maydonlar (sarlavha, annotatsiya,
 * kalit so'zlar) har bir til uchun alohida, xml:lang bilan chiqadi — BASE, OpenAIRE va
 * CyberLeninka shu ko'rinishni kutadi.
 */
class DublinCore
{
    /** Bog'liq yozuvlar (N+1 bo'lmasligi uchun ro'yxatlarda oldindan yuklanadi) */
    public const RELATIONS = ['authors', 'subject', 'placement.issue'];

    /** ISO 639-3 kodlari (dc:language uchun tavsiya etilgan) */
    private const ISO_639_3 = ['uz' => 'uzb', 'ru' => 'rus', 'en' => 'eng'];

    /**
     * @return list<array{0: string, 1: string, 2: string|null}>
     */
    public function elements(Article $article): array
    {
        $elements = [];
        $add = function (string $name, mixed $value, ?string $lang = null) use (&$elements): void {
            if (is_scalar($value)) {
                $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)) ?? '');

                if ($text !== '') {
                    $elements[] = [$name, $text, $lang];
                }
            }
        };

        foreach ($this->translations($article, 'title') as $lang => $title) {
            $add('title', $title, $lang);
        }

        /** @var ArticleAuthor $author */
        foreach ($article->authors->sortBy('sort_order') as $author) {
            $add('creator', trim($author->last_name.', '.$author->first_name.' '.($author->middle_name ?? '')));
        }

        if ($article->subject !== null) {
            foreach ($this->translations($article->subject, 'name') as $lang => $name) {
                $add('subject', $name, $lang);
            }
        }

        foreach ($this->translations($article, 'keywords') as $lang => $keywords) {
            foreach ((array) $keywords as $keyword) {
                $add('subject', $keyword, $lang);
            }
        }

        foreach ($this->translations($article, 'abstract') as $lang => $abstract) {
            $add('description', $abstract, $lang);
        }

        $journal = self::journalName();
        $add('publisher', $journal);

        if ($article->published_at !== null) {
            $add('date', $article->published_at->utc()->format('Y-m-d'));
        }

        $add('type', 'info:eu-repo/semantics/article');
        $add('type', 'info:eu-repo/semantics/publishedVersion');
        $add('type', 'Text');
        $add('format', 'application/pdf');

        $add('identifier', route('articles.show', (string) $article->slug));

        if ($article->doi !== null && $article->doi !== '') {
            $add('identifier', 'https://doi.org/'.$article->doi);
        }

        $add('source', $this->source($article, $journal));

        foreach (['issn', 'eissn'] as $key) {
            $issn = config('journal.'.$key);

            if (is_string($issn) && $issn !== '') {
                $add('source', 'ISSN '.$issn);
            }
        }

        $add('language', self::ISO_639_3[$article->language] ?? $article->language);
        $add('rights', 'info:eu-repo/semantics/openAccess');

        return $elements;
    }

    /** "Inson va Jamiyat; Vol. 2, No. 3 (2026); 45-58" */
    private function source(Article $article, string $journal): string
    {
        $placement = $article->placement;
        $issue = $placement?->issue;

        if (! $issue instanceof JournalIssue) {
            return $journal;
        }

        $parts = [$journal];
        $numbering = trim(($issue->volume !== null ? 'Vol. '.$issue->volume.', ' : '').'No. '.$issue->number.' ('.$issue->year.')');
        $parts[] = $numbering;

        if ($placement->page_from !== null) {
            $parts[] = $placement->page_from.($placement->page_to !== null ? '-'.$placement->page_to : '');
        }

        return implode('; ', $parts);
    }

    /**
     * Tarjimali maydon: barcha mavjud tillar (asosiy til birinchi).
     *
     * @param  Article|Subject  $model
     * @return array<string, mixed>
     */
    private function translations(object $model, string $field): array
    {
        /** @var array<string, mixed> $values */
        $values = $model->getTranslations($field);
        $ordered = [];
        $primary = $model instanceof Article ? $model->language : Language::default()->value;

        foreach ([$primary, ...Language::values()] as $lang) {
            $value = $values[$lang] ?? null;

            if (! isset($ordered[$lang]) && $value !== null && $value !== '' && $value !== []) {
                $ordered[$lang] = $value;
            }
        }

        return $ordered;
    }

    public static function journalName(): string
    {
        $name = config('journal.name');

        return is_string($name) && $name !== '' ? $name : 'Inson va Jamiyat';
    }
}
