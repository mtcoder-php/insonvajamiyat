<?php

namespace App\Services\Indexing;

use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\IssueArticle;
use App\Models\JournalIssue;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use RuntimeException;
use XMLWriter;

/**
 * Crossref DOI deposit XML (schema 5.4.0) — bitta jurnal soni uchun.
 *
 * Fayl Crossref kabinetida (https://doi.crossref.org → Submissions → Upload) yuklanadi yoki
 * deposit API'ga yuboriladi. Faqat DOI'si bor va saytda nashr etilgan maqolalar kiradi.
 *
 * @see https://www.crossref.org/documentation/schema-library/markup-guide-record-types/journals-and-articles/
 */
class CrossrefDeposit
{
    public const SCHEMA_VERSION = '5.4.0';

    private const NS = 'http://www.crossref.org/schema/5.4.0';

    private const JATS = 'http://www.ncbi.nlm.nih.gov/JATS1';

    /**
     * Crossref'ga yaroqli maqolalar (DOI bor, nashr etilgan, slug bor).
     *
     * @return Collection<int, Article>
     */
    public function articles(JournalIssue $issue): Collection
    {
        return IssueArticle::query()
            ->where('journal_issue_id', $issue->id)
            ->with(['article.authors'])
            ->orderBy('position')
            ->get()
            ->map(fn (IssueArticle $placement): Article => $placement->article->setRelation('placement', $placement))
            ->filter(fn (Article $article): bool => $article->isPublished()
                && $article->doi !== null && $article->doi !== ''
                && $article->published_at !== null)
            ->values();
    }

    /**
     * @throws RuntimeException Yaroqli maqola bo'lmasa
     */
    public function build(JournalIssue $issue, ?CarbonInterface $now = null): string
    {
        $articles = $this->articles($issue);

        if ($articles->isEmpty()) {
            throw new RuntimeException('Sonda DOI\'si bor nashr etilgan maqola yo\'q.');
        }

        $now = CarbonImmutable::instance($now ?? CarbonImmutable::now())->utc();
        $journal = DublinCore::journalName();
        $email = config('journal.contact.email');

        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');

        $xml->startElement('doi_batch');
        $xml->writeAttribute('version', self::SCHEMA_VERSION);
        $xml->writeAttribute('xmlns', self::NS);
        $xml->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->writeAttribute('xmlns:jats', self::JATS);
        $xml->writeAttribute('xsi:schemaLocation', self::NS.' https://www.crossref.org/schemas/crossref'.self::SCHEMA_VERSION.'.xsd');

        // head
        $xml->startElement('head');
        $xml->writeElement('doi_batch_id', 'ivj-'.$issue->slug.'-'.$now->format('YmdHis'));
        $xml->writeElement('timestamp', $now->format('YmdHisv'));
        $xml->startElement('depositor');
        $xml->writeElement('depositor_name', $journal);
        $xml->writeElement('email_address', is_string($email) && $email !== '' ? $email : 'info@insonvajamiyat.uz');
        $xml->endElement();
        $xml->writeElement('registrant', $journal);
        $xml->endElement();

        $xml->startElement('body');
        $xml->startElement('journal');

        // journal_metadata: full_title, issn
        $xml->startElement('journal_metadata');
        $xml->writeAttribute('language', 'uz');
        $xml->writeElement('full_title', $journal);

        foreach (['issn' => 'print', 'eissn' => 'electronic'] as $key => $media) {
            $issn = config('journal.'.$key);

            if (is_string($issn) && preg_match('/^\d{4}-?\d{3}[\dX]$/', $issn) === 1) {
                $xml->startElement('issn');
                $xml->writeAttribute('media_type', $media);
                $xml->text($issn);
                $xml->endElement();
            }
        }

        $xml->endElement();

        // journal_issue: publication_date, journal_volume, issue, doi_data
        $xml->startElement('journal_issue');
        $this->date($xml, CarbonImmutable::instance($issue->published_at ?? $now));

        if ($issue->volume !== null) {
            $xml->startElement('journal_volume');
            $xml->writeElement('volume', (string) $issue->volume);
            $xml->endElement();
        }

        $xml->writeElement('issue', (string) $issue->number);

        if ($issue->doi !== null && $issue->doi !== '') {
            $this->doiData($xml, $issue->doi, route('issues.show', $issue->slug));
        }

        $xml->endElement();

        foreach ($articles as $article) {
            $this->article($xml, $article);
        }

        $xml->endElement(); // journal
        $xml->endElement(); // body
        $xml->endElement(); // doi_batch
        $xml->endDocument();

        return $xml->outputMemory();
    }

    /**
     * journal_article: titles, contributors, jats:abstract, publication_date, pages, doi_data
     * (tartib Crossref sxemasidagi ketma-ketlikka mos).
     */
    private function article(XMLWriter $xml, Article $article): void
    {
        $xml->startElement('journal_article');
        $xml->writeAttribute('publication_type', 'full_text');
        $xml->writeAttribute('language', $article->language);

        $xml->startElement('titles');
        $xml->writeElement('title', self::text((string) $article->getTranslation('title', $article->language, true)));
        $xml->endElement();

        $authors = $article->authors->sortBy('sort_order')->values();

        if ($authors->isNotEmpty()) {
            $xml->startElement('contributors');

            /** @var ArticleAuthor $author */
            foreach ($authors as $index => $author) {
                $xml->startElement('person_name');
                $xml->writeAttribute('sequence', $index === 0 ? 'first' : 'additional');
                $xml->writeAttribute('contributor_role', 'author');
                $xml->writeElement('given_name', self::text(trim($author->first_name.' '.($author->middle_name ?? ''))));
                $xml->writeElement('surname', self::text($author->last_name));

                if ($author->organization !== null && $author->organization !== '') {
                    $xml->startElement('affiliations');
                    $xml->startElement('institution');
                    $xml->writeElement('institution_name', self::text($author->organization));
                    $xml->endElement();
                    $xml->endElement();
                }

                if ($author->orcid !== null && preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/', $author->orcid) === 1) {
                    $xml->writeElement('ORCID', 'https://orcid.org/'.$author->orcid);
                }

                $xml->endElement();
            }

            $xml->endElement();
        }

        foreach ($article->getTranslations('abstract') as $lang => $abstract) {
            $text = self::text(is_string($abstract) ? $abstract : '');

            if ($text !== '') {
                $xml->startElement('jats:abstract');
                $xml->writeAttribute('xml:lang', (string) $lang);
                $xml->writeElement('jats:p', $text);
                $xml->endElement();
            }
        }

        $this->date($xml, CarbonImmutable::instance($article->published_at ?? CarbonImmutable::now()));

        $placement = $article->placement;

        if ($placement?->page_from !== null) {
            $xml->startElement('pages');
            $xml->writeElement('first_page', (string) $placement->page_from);

            if ($placement->page_to !== null && $placement->page_to !== $placement->page_from) {
                $xml->writeElement('last_page', (string) $placement->page_to);
            }

            $xml->endElement();
        }

        $this->doiData($xml, (string) $article->doi, route('articles.show', (string) $article->slug));

        $xml->endElement();
    }

    private function date(XMLWriter $xml, CarbonImmutable $date): void
    {
        $xml->startElement('publication_date');
        $xml->writeAttribute('media_type', 'online');
        $xml->writeElement('month', $date->format('m'));
        $xml->writeElement('day', $date->format('d'));
        $xml->writeElement('year', $date->format('Y'));
        $xml->endElement();
    }

    private function doiData(XMLWriter $xml, string $doi, string $url): void
    {
        $xml->startElement('doi_data');
        $xml->writeElement('doi', $doi);
        $xml->writeElement('resource', $url);
        $xml->endElement();
    }

    /** HTML teglarsiz, bitta qatorli matn (XML 1.0 da taqiqlangan belgilarsiz) */
    private static function text(string $value): string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', strip_tags($value)));

        return (string) preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value);
    }
}
