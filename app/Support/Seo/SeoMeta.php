<?php

namespace App\Support\Seo;

use App\Enums\ArticleFileType;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\Event;
use App\Models\JournalIssue;
use App\Models\Post;
use App\Support\MediaUrl;
use Illuminate\Support\Str;

/**
 * Server tomonida chiqadigan SEO teglari (resources/views/app.blade.php).
 *
 * Inertia sahifalari JS orqali chiziladi; ijtimoiy tarmoqlar (Telegram, Facebook) va
 * Google Scholar esa sahifaning dastlabki HTML'ini o'qiydi — shuning uchun title, description,
 * Open Graph, canonical va citation_* teglari shu yerdan beriladi.
 * So'rov davomida bitta nusxa (scoped): controller set()/forArticle() chaqiradi.
 * Admin, kabinet, kirish sahifalari va production bo'lmagan muhit — noindex.
 */
class SeoMeta
{
    /** Controller title bermagan ommaviy sahifalar */
    private const PAGE_TITLES = [
        'web/About' => 'Jurnal haqida',
        'web/Guidelines' => "Mualliflar uchun yo'riqnoma",
        'web/Contact' => 'Aloqa',
        'web/articles/Index' => 'Maqolalar katalogi',
        'web/issues/Index' => 'Jurnal sonlari arxivi',
        'web/news/Index' => "Yangiliklar va e'lonlar",
        'web/events/Index' => 'Tadbirlar',
    ];

    private ?string $title = null;

    private ?string $description = null;

    private ?string $image = null;

    private string $type = 'website';

    private ?string $canonical = null;

    private bool $noindex = false;

    /** @var array<int, array{0: string, 1: string}> citation_* va boshqa name="" teglar */
    private array $meta = [];

    /** @var array<string, mixed>|null */
    private ?array $jsonLd = null;

    public function set(?string $title = null, ?string $description = null, ?string $image = null, string $type = 'website'): static
    {
        $this->title = $title;
        $this->description = $description !== null ? self::clean($description) : null;
        $this->image = $image;
        $this->type = $type;

        return $this;
    }

    public function noindex(bool $value = true): static
    {
        $this->noindex = $value;

        return $this;
    }

    public function forArticle(Article $article): static
    {
        $article->loadMissing(['authors', 'placement.issue', 'subject']);

        $abstract = $article->abstract;
        $this->set(
            $article->title,
            is_string($abstract) ? $abstract : null,
            MediaUrl::from($article->cover_image_path),
            'article',
        );

        $journal = self::journal();
        $placement = $article->placement;
        $issue = $placement?->issue;
        $url = route('articles.show', (string) $article->slug);

        $this->add('citation_title', $article->title);

        /** @var ArticleAuthor $author */
        foreach ($article->authors as $author) {
            $this->add('citation_author', trim($author->last_name.', '.$author->first_name.' '.($author->middle_name ?? '')));

            if ($author->organization) {
                $this->add('citation_author_institution', $author->organization);
            }

            if ($author->orcid) {
                $this->add('citation_author_orcid', 'https://orcid.org/'.$author->orcid);
            }
        }

        if ($article->published_at !== null) {
            $this->add('citation_publication_date', $article->published_at->format('Y/m/d'));
        }

        $this->add('citation_journal_title', $journal);
        $this->add('citation_publisher', $journal);

        foreach (['issn', 'eissn'] as $key) {
            $issn = config('journal.'.$key);

            if (is_string($issn) && $issn !== '') {
                $this->add('citation_issn', $issn);
            }
        }

        if ($issue instanceof JournalIssue) {
            $this->add('citation_volume', (string) ($issue->volume ?? $issue->year));
            $this->add('citation_issue', (string) $issue->number);
        }

        if ($placement?->page_from !== null) {
            $this->add('citation_firstpage', (string) $placement->page_from);
        }

        if ($placement?->page_to !== null) {
            $this->add('citation_lastpage', (string) $placement->page_to);
        }

        if ($article->doi) {
            $this->add('citation_doi', $article->doi);
        }

        $this->add('citation_language', $article->language);
        $this->add('citation_abstract_html_url', $url);

        $keywords = $article->keywords;

        if (is_array($keywords) && $keywords !== []) {
            $this->add('citation_keywords', implode('; ', array_filter($keywords, 'is_string')));
        }

        if ($article->files()->where('type', ArticleFileType::FinalPdf->value)->exists()) {
            $this->add('citation_pdf_url', route('articles.pdf', (string) $article->slug));
        }

        $this->jsonLd = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ScholarlyArticle',
            'headline' => Str::limit($article->title, 110, ''),
            'name' => $article->title,
            'url' => $url,
            'abstract' => $this->description,
            'datePublished' => $article->published_at?->toDateString(),
            'inLanguage' => $article->language,
            'about' => $article->subject?->name,
            'identifier' => $article->doi ? 'https://doi.org/'.$article->doi : null,
            'author' => $article->authors->map(fn (ArticleAuthor $a): array => array_filter([
                '@type' => 'Person',
                'name' => trim($a->first_name.' '.$a->last_name),
                'affiliation' => $a->organization ? ['@type' => 'Organization', 'name' => $a->organization] : null,
                'sameAs' => $a->orcid ? 'https://orcid.org/'.$a->orcid : null,
            ]))->values()->all(),
            'isPartOf' => array_filter([
                '@type' => 'PublicationIssue',
                'issueNumber' => $issue?->number,
                'datePublished' => $issue?->published_at?->toDateString(),
                'isPartOf' => array_filter(['@type' => 'Periodical', 'name' => $journal, 'issn' => config('journal.issn') ?: null]),
            ]),
        ], fn (mixed $v): bool => $v !== null && $v !== []);

        return $this;
    }

    public function forIssue(JournalIssue $issue): static
    {
        $description = $issue->description;
        $fallback = __(':journal ilmiy jurnalining :year-yil :number-soni', ['journal' => self::journal(), 'year' => $issue->year, 'number' => $issue->number]);

        return $this->set(
            self::journal().' — '.$issue->year.', №'.$issue->number,
            $description !== null && $description !== '' ? $description : (is_string($fallback) ? $fallback : null),
            MediaUrl::from($issue->cover_image_path),
        );
    }

    public function forPost(Post $post): static
    {
        return $this->set($post->title, $post->excerpt ?: Str::limit((string) $post->body, 200), MediaUrl::from($post->image_path), 'article');
    }

    public function forEvent(Event $event): static
    {
        return $this->set($event->title, Str::limit((string) $event->description, 200), MediaUrl::from($event->image_path));
    }

    /**
     * Blade uchun yakuniy qiymatlar.
     *
     * @return array{title: string, description: string, image: string|null, type: string, url: string, canonical: string, siteName: string, robots: string|null, meta: array<int, array{0: string, 1: string}>, jsonLd: string|null}
     */
    public function resolve(string $component = ''): array
    {
        $journal = self::journal();
        $default = config('journal.description');
        $public = $component === '' || str_starts_with($component, 'web/');
        $url = request()->url();
        $page = $this->title ?? (self::PAGE_TITLES[$component] ?? null);
        $title = $page !== null && $page !== '' ? $page.' — '.$journal : $journal;
        $image = $this->image ?? MediaUrl::publicAsset('images/og-default.png') ?? asset('apple-touch-icon.png');
        $jsonLd = $this->jsonLd !== null ? json_encode($this->jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) : null;

        return [
            'title' => $title,
            'description' => Str::limit($this->description ?? (is_string($default) ? $default : ''), 300),
            'image' => $image,
            'type' => $this->type,
            'url' => $url,
            'canonical' => $this->canonical ?? $url,
            'siteName' => $journal,
            'robots' => $this->noindex || ! $public || ! app()->environment('production') ? 'noindex, nofollow' : null,
            'meta' => $this->meta,
            'jsonLd' => $jsonLd === false ? null : $jsonLd,
        ];
    }

    private function add(string $name, ?string $content): void
    {
        if ($content !== null && trim($content) !== '') {
            $this->meta[] = [$name, trim($content)];
        }
    }

    private static function clean(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', strip_tags($text)));
    }

    private static function journal(): string
    {
        $name = config('journal.name');

        return is_string($name) && $name !== '' ? $name : 'Inson va Jamiyat';
    }
}
