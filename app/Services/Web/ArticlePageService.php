<?php

namespace App\Services\Web;

use App\Enums\ArticleFileType;
use App\Enums\Language;
use App\Http\Resources\Web\ArticleCardResource;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleFile;
use App\Models\IssueArticle;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Nashr etilgan maqola sahifasi (web maqola view page.png): meta ma'lumotlar, mualliflar va
 * tashkilotlar, PDF, iqtibos formatlari (APA / GOST), son ichidagi qo'shni maqolalar,
 * tegishli maqolalar.
 */
class ArticlePageService
{
    public const RELATED = 4;

    /**
     * @return array<string, mixed>
     */
    public function show(Article $article): array
    {
        // SeoMeta::forArticle oldinroq yuklagan bo'lishi mumkin — qayta so'rov yubormaymiz
        $article->loadMissing(['authors', 'subject', 'articleType', 'placement.issue']);
        $placement = $article->placement;
        $issue = $placement?->issue;
        $pdf = $this->finalPdf($article);
        $authors = $article->authors->sortBy('sort_order')->values();

        // Tashkilotlar ro'yxati (takrorlanmas) va har bir muallifning tashkilot raqami
        $affiliations = $authors->pluck('organization')->filter()->unique()->values();

        $keywords = $article->getTranslation('keywords', app()->getLocale(), true);
        $language = Language::tryFrom($article->language);

        return [
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->title,
            'abstract' => $article->abstract,
            'keywords' => is_array($keywords) ? array_values(array_filter($keywords, 'is_string')) : [],
            'references' => $article->references,
            'subject' => $article->subject !== null ? ['name' => $article->subject->name, 'slug' => $article->subject->slug] : null,
            'language' => $language?->label() ?? $article->language,
            'type' => $article->articleType->name,
            'doi' => $article->doi,
            'doiUrl' => $article->doi !== null ? 'https://doi.org/'.$article->doi : null,
            'udc' => $article->udc,
            'coverUrl' => MediaUrl::from($article->cover_image_path),
            'views' => $article->views_count,
            'downloads' => $article->downloads_count,
            'submittedAt' => $article->submitted_at?->toIso8601String(),
            'acceptedAt' => $article->accepted_at?->toIso8601String(),
            'publishedAt' => $article->published_at?->toIso8601String(),
            'pages' => $placement?->pages(),
            'issue' => $issue !== null ? [
                'label' => $issue->label,
                'year' => $issue->year,
                'number' => $issue->number,
                'volume' => $issue->volume,
                'url' => route('issues.show', $issue->slug),
            ] : null,
            'authors' => $authors->map(fn (ArticleAuthor $a): array => [
                'name' => $a->full_name,
                'shortName' => $a->short_name,
                'organization' => $a->organization,
                'affiliation' => $this->affiliationIndex($affiliations->all(), $a->organization),
                'degree' => $a->academic_degree,
                'orcid' => $a->orcid,
                'isCorresponding' => $a->is_corresponding,
            ])->all(),
            'affiliations' => $affiliations->all(),
            'pdf' => $pdf !== null ? [
                'viewUrl' => route('articles.pdf', $article->slug),
                'downloadUrl' => route('articles.pdf', [$article->slug, 'download' => 1]),
                'size' => $pdf->size,
            ] : null,
            'citations' => [
                'apa' => $this->apa($article, $authors->all()),
                'gost' => $this->gost($article, $authors->all()),
            ],
            'neighbours' => $this->neighbours($article, $placement),
            'related' => ArticleCardResource::collection($this->related($article))->resolve(),
        ];
    }

    public function finalPdf(Article $article): ?ArticleFile
    {
        return $article->files()
            ->where('type', ArticleFileType::FinalPdf->value)
            ->latest('id')
            ->first();
    }

    /**
     * APA 7: Karimov, A. S., Jo'rayev, B. T., & Sodiqova, M. X. (2025). Sarlavha. Inson va Jamiyat, 3, 45–58. https://doi.org/...
     *
     * @param  array<int, ArticleAuthor>  $authors
     */
    public function apa(Article $article, array $authors): string
    {
        $names = array_map(
            fn (ArticleAuthor $a): string => trim($a->last_name.', '.$this->initials($a)),
            $authors,
        );
        $last = array_pop($names);
        $list = $names === [] ? (string) $last : implode(', ', $names).', & '.$last;
        $issue = $article->placement?->issue;
        $pages = $article->placement?->pages();

        $parts = [
            $list,
            '('.($article->published_at ?? now())->year.').',
            rtrim($article->title, '.').'.',
            $this->journal().($issue !== null ? ', '.$issue->number : '').($pages !== null ? ', '.$pages : '').'.',
        ];

        if ($article->doi !== null) {
            $parts[] = 'https://doi.org/'.$article->doi;
        }

        return implode(' ', array_filter($parts, fn (string $p): bool => $p !== ''));
    }

    /**
     * GOST R 7.0.100: Karimov A. S., Jo'rayev B. T. Sarlavha // Inson va Jamiyat. – 2025. – № 3. – B. 45–58. – DOI: ...
     *
     * @param  array<int, ArticleAuthor>  $authors
     */
    public function gost(Article $article, array $authors): string
    {
        $names = implode(', ', array_map(
            fn (ArticleAuthor $a): string => trim($a->last_name.' '.$this->initials($a)),
            $authors,
        ));
        $issue = $article->placement?->issue;
        $pages = $article->placement?->pages();

        $text = trim($names.' '.rtrim($article->title, '.')).' // '.$this->journal()
            .'. – '.($article->published_at ?? now())->year
            .($issue !== null ? '. – № '.$issue->number : '')
            .($pages !== null ? '. – B. '.$pages : '')
            .'.';

        return $article->doi !== null ? $text.' – DOI: '.$article->doi.'.' : $text;
    }

    /**
     * @param  array<int, mixed>  $affiliations
     */
    private function affiliationIndex(array $affiliations, ?string $organization): ?int
    {
        if ($organization === null) {
            return null;
        }

        $index = array_search($organization, $affiliations, true);

        return is_int($index) ? $index + 1 : null;
    }

    private function initials(ArticleAuthor $author): string
    {
        return collect([$author->first_name, $author->middle_name])
            ->filter()
            ->map(fn (string $name): string => Str::upper(Str::substr($name, 0, 1)).'.')
            ->implode(' ');
    }

    private function journal(): string
    {
        $name = config('journal.name');

        return is_string($name) ? $name : 'Inson va Jamiyat';
    }

    /**
     * Son ichidagi oldingi va keyingi (nashr etilgan) maqola.
     *
     * @return array{prev: array{title: string, url: string}|null, next: array{title: string, url: string}|null}
     */
    private function neighbours(Article $article, ?IssueArticle $placement): array
    {
        if ($placement === null) {
            return ['prev' => null, 'next' => null];
        }

        $siblings = IssueArticle::query()
            ->where('journal_issue_id', $placement->journal_issue_id)
            ->whereHas('article', fn (Builder $q) => $q->published())
            // Faqat sarlavha va havola kerak (annotatsiya, adabiyotlar yuklanmaydi)
            ->with('article:id,title,slug')
            ->orderBy('position')
            ->get()
            ->values();

        $index = $siblings->search(fn (IssueArticle $p): bool => $p->article_id === $article->id);

        if (! is_int($index)) {
            return ['prev' => null, 'next' => null];
        }

        $link = function (?IssueArticle $p): ?array {
            return $p !== null ? ['title' => $p->article->title, 'url' => route('articles.show', (string) $p->article->slug)] : null;
        };

        return [
            'prev' => $index > 0 ? $link($siblings->get($index - 1)) : null,
            'next' => $link($siblings->get($index + 1)),
        ];
    }

    /**
     * Shu yo'nalishdagi boshqa nashr etilgan maqolalar (yangilari).
     *
     * @return Collection<int, Article>
     */
    private function related(Article $article): Collection
    {
        return Article::query()
            ->published()
            ->whereKeyNot($article->id)
            ->when($article->subject_id !== null, fn (Builder $q) => $q->where('subject_id', $article->subject_id))
            ->with(['authors', 'subject'])
            ->latest('published_at')
            ->limit(self::RELATED)
            ->get();
    }
}
