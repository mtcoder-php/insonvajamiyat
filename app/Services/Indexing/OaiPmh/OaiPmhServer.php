<?php

namespace App\Services\Indexing\OaiPmh;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\JournalIssue;
use App\Models\Subject;
use App\Services\Indexing\DublinCore;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;
use XMLWriter;

/**
 * OAI-PMH 2.0 repozitoriy (https://www.openarchives.org/OAI/openarchivesprotocol.html).
 *
 * Ilmiy bazalar (BASE, OpenAIRE, CyberLeninka, Google Scholar va boshq.) nashr etilgan
 * maqolalarni shu orqali avtomatik yig'adi.
 *
 *  - Yozuvlar: nashr etilgan maqolalar; metadata formati — oai_dc (Dublin Core)
 *  - To'plamlar (sets): subject:{slug} (ilmiy yo'nalish), issue:{slug} (jurnal soni)
 *  - O'chirilgan yozuvlar: transient — saytdan olib tashlangan maqola status="deleted" bilan qaytadi
 *  - Sana aniqligi: YYYY-MM-DDThh:mm:ssZ (UTC); sahifa — 100 yozuv, resumptionToken bilan
 */
class OaiPmhServer
{
    public const PAGE_SIZE = 100;

    public const METADATA_PREFIX = 'oai_dc';

    private const VERBS = [
        'Identify' => ['required' => [], 'optional' => []],
        'ListMetadataFormats' => ['required' => [], 'optional' => ['identifier']],
        'ListSets' => ['required' => [], 'optional' => [], 'exclusive' => 'resumptionToken'],
        'ListIdentifiers' => ['required' => ['metadataPrefix'], 'optional' => ['from', 'until', 'set'], 'exclusive' => 'resumptionToken'],
        'ListRecords' => ['required' => ['metadataPrefix'], 'optional' => ['from', 'until', 'set'], 'exclusive' => 'resumptionToken'],
        'GetRecord' => ['required' => ['identifier', 'metadataPrefix'], 'optional' => []],
    ];

    private XMLWriter $xml;

    public function __construct(private readonly DublinCore $dublinCore) {}

    /**
     * @param  list<array{0: string, 1: string}>  $pairs  So'rov argumentlari (takrorlanganini aniqlash uchun ro'yxat)
     */
    public function handle(array $pairs, string $baseUrl): string
    {
        $this->xml = new XMLWriter;
        $this->xml->openMemory();
        $this->xml->setIndent(true);
        $this->xml->startDocument('1.0', 'UTF-8');
        $this->xml->startElement('OAI-PMH');
        $this->xml->writeAttribute('xmlns', 'http://www.openarchives.org/OAI/2.0/');
        $this->xml->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $this->xml->writeAttribute('xsi:schemaLocation', 'http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd');
        $this->xml->writeElement('responseDate', self::datestamp(CarbonImmutable::now()));
        try {
            $args = $this->validate($pairs);
        } catch (OaiException $e) {
            // badVerb / badArgument: <request> atributsiz yoziladi (protokol talabi)
            $this->request($baseUrl, []);
            $this->error($e);
            $args = null;
        }

        if ($args !== null) {
            $this->request($baseUrl, $args);
            $verb = $args['verb'];

            try {
                match ($verb) {
                    'Identify' => $this->identify($baseUrl),
                    'ListMetadataFormats' => $this->listMetadataFormats($args),
                    'ListSets' => $this->listSets($args),
                    'ListIdentifiers', 'ListRecords' => $this->listRecords($args, $verb === 'ListRecords'),
                    default => $this->getRecord($args),
                };
            } catch (OaiException $e) {
                $this->error($e);
            }
        }

        $this->xml->endElement();
        $this->xml->endDocument();

        return $this->xml->outputMemory();
    }

    /**
     * @param  list<array{0: string, 1: string}>  $pairs
     * @return array<string, string>
     *
     * @throws OaiException
     */
    private function validate(array $pairs): array
    {
        $args = [];

        foreach ($pairs as [$key, $value]) {
            if (array_key_exists($key, $args)) {
                throw OaiException::badArgument("The argument '{$key}' is repeated.");
            }

            $args[$key] = $value;
        }

        $verb = $args['verb'] ?? null;

        if (! is_string($verb) || ! isset(self::VERBS[$verb])) {
            throw new OaiException('badVerb', 'Value of the verb argument is not a legal OAI-PMH verb, the verb argument is missing, or the verb argument is repeated.');
        }

        $spec = self::VERBS[$verb];
        $given = array_diff(array_keys($args), ['verb']);
        $exclusive = $spec['exclusive'] ?? null;

        if ($exclusive !== null && array_key_exists($exclusive, $args)) {
            if (count($given) !== 1) {
                throw OaiException::badArgument("The argument '{$exclusive}' is exclusive.");
            }

            return $args;
        }

        foreach ($spec['required'] as $key) {
            if (! isset($args[$key]) || $args[$key] === '') {
                throw OaiException::badArgument("Missing required argument '{$key}'.");
            }
        }

        $illegal = array_diff($given, $spec['required'], $spec['optional']);

        if ($illegal !== []) {
            throw OaiException::badArgument('Illegal argument: '.implode(', ', $illegal).'.');
        }

        // Sana va to'plam sintaksisi — <request> yozilishidan oldin (badArgument'da u atributsiz bo'ladi)
        $from = isset($args['from']) ? self::parseDate($args['from'], false) : null;
        $until = isset($args['until']) ? self::parseDate($args['until'], true) : null;

        if (isset($args['from'], $args['until']) && strlen($args['from']) !== strlen($args['until'])) {
            throw OaiException::badArgument('The from and until arguments must have the same granularity.');
        }

        if ($from !== null && $until !== null && $from->greaterThan($until)) {
            throw OaiException::badArgument('The from argument must be less than or equal to the until argument.');
        }

        if (isset($args['set']) && preg_match('/^(subject|issue):[A-Za-z0-9\-_.]+$/', $args['set']) !== 1) {
            throw OaiException::badArgument('Unknown set: '.$args['set']);
        }

        return $args;
    }

    /**
     * @param  array<string, string>  $args
     */
    private function request(string $baseUrl, array $args): void
    {
        $this->xml->startElement('request');

        foreach ($args as $key => $value) {
            $this->xml->writeAttribute($key, $value);
        }

        $this->xml->text($baseUrl);
        $this->xml->endElement();
    }

    private function error(OaiException $e): void
    {
        $this->xml->startElement('error');
        $this->xml->writeAttribute('code', $e->oaiCode);
        $this->xml->text($e->getMessage());
        $this->xml->endElement();
    }

    private function identify(string $baseUrl): void
    {
        $earliest = $this->baseQuery()->min('updated_at');
        $email = config('journal.contact.email');

        $this->xml->startElement('Identify');
        $this->xml->writeElement('repositoryName', DublinCore::journalName());
        $this->xml->writeElement('baseURL', $baseUrl);
        $this->xml->writeElement('protocolVersion', '2.0');
        $this->xml->writeElement('adminEmail', is_string($email) && $email !== '' ? $email : 'info@'.self::repositoryId());
        $this->xml->writeElement('earliestDatestamp', self::datestamp(
            $earliest !== null ? CarbonImmutable::parse((string) $earliest) : CarbonImmutable::now(),
        ));
        $this->xml->writeElement('deletedRecord', 'transient');
        $this->xml->writeElement('granularity', 'YYYY-MM-DDThh:mm:ssZ');

        $this->xml->startElement('description');
        $this->xml->startElement('oai-identifier');
        $this->xml->writeAttribute('xmlns', 'http://www.openarchives.org/OAI/2.0/oai-identifier');
        $this->xml->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $this->xml->writeAttribute('xsi:schemaLocation', 'http://www.openarchives.org/OAI/2.0/oai-identifier http://www.openarchives.org/OAI/2.0/oai-identifier.xsd');
        $this->xml->writeElement('scheme', 'oai');
        $this->xml->writeElement('repositoryIdentifier', self::repositoryId());
        $this->xml->writeElement('delimiter', ':');
        $this->xml->writeElement('sampleIdentifier', 'oai:'.self::repositoryId().':article/1');
        $this->xml->endElement();
        $this->xml->endElement();

        $this->xml->endElement();
    }

    /**
     * @param  array<string, string>  $args
     */
    private function listMetadataFormats(array $args): void
    {
        if (isset($args['identifier'])) {
            $this->findArticle($args['identifier']);
        }

        $this->xml->startElement('ListMetadataFormats');
        $this->xml->startElement('metadataFormat');
        $this->xml->writeElement('metadataPrefix', self::METADATA_PREFIX);
        $this->xml->writeElement('schema', 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd');
        $this->xml->writeElement('metadataNamespace', 'http://www.openarchives.org/OAI/2.0/oai_dc/');
        $this->xml->endElement();
        $this->xml->endElement();
    }

    /**
     * @param  array<string, string>  $args
     */
    private function listSets(array $args): void
    {
        if (isset($args['resumptionToken'])) {
            throw new OaiException('badResumptionToken', 'The value of the resumptionToken argument is invalid or expired.');
        }

        $this->xml->startElement('ListSets');

        Subject::query()->orderBy('id')->get()->each(function (Subject $subject): void {
            $this->set('subject:'.$subject->slug, $subject->name);
        });

        JournalIssue::query()->published()->orderBy('year')->orderBy('number')->get()
            ->each(function (JournalIssue $issue): void {
                $this->set('issue:'.$issue->slug, DublinCore::journalName().' '.$issue->label);
            });

        $this->xml->endElement();
    }

    private function set(string $spec, string $name): void
    {
        $this->xml->startElement('set');
        $this->xml->writeElement('setSpec', $spec);
        $this->xml->writeElement('setName', $name);
        $this->xml->endElement();
    }

    /**
     * @param  array<string, string>  $args
     */
    private function listRecords(array $args, bool $withMetadata): void
    {
        $token = isset($args['resumptionToken'])
            ? ResumptionToken::decode($args['resumptionToken'])
            : new ResumptionToken($args['metadataPrefix'], $args['from'] ?? null, $args['until'] ?? null, $args['set'] ?? null, 0, 0);

        $this->ensurePrefix($token->metadataPrefix);

        $query = $this->filteredQuery($token);
        $total = (clone $query)->count();

        /** @var Collection<int, Article> $articles */
        $articles = (clone $query)
            ->where('articles.id', '>', $token->afterId)
            ->with(DublinCore::RELATIONS)
            ->orderBy('articles.id')
            ->limit(self::PAGE_SIZE)
            ->get();

        if ($articles->isEmpty()) {
            if ($token->cursor > 0) {
                throw new OaiException('badResumptionToken', 'The value of the resumptionToken argument is invalid or expired.');
            }

            throw new OaiException('noRecordsMatch', 'The combination of the values of the from, until, set and metadataPrefix arguments results in an empty list.');
        }

        $this->xml->startElement($withMetadata ? 'ListRecords' : 'ListIdentifiers');

        foreach ($articles as $article) {
            $withMetadata ? $this->record($article) : $this->header($article);
        }

        $shown = $token->cursor + $articles->count();
        $last = $articles->last();

        // Oxirgi sahifadan keyin — bo'sh resumptionToken (protokol talabi)
        if (isset($args['resumptionToken']) || $shown < $total) {
            $this->xml->startElement('resumptionToken');
            $this->xml->writeAttribute('completeListSize', (string) $total);
            $this->xml->writeAttribute('cursor', (string) $token->cursor);

            if ($shown < $total) {
                $this->xml->text((new ResumptionToken(
                    $token->metadataPrefix, $token->from, $token->until, $token->set, $last->id, $shown,
                ))->encode());
            }

            $this->xml->endElement();
        }

        $this->xml->endElement();
    }

    /**
     * @param  array<string, string>  $args
     */
    private function getRecord(array $args): void
    {
        $this->ensurePrefix($args['metadataPrefix']);
        $article = $this->findArticle($args['identifier']);
        $article->load(DublinCore::RELATIONS);

        $this->xml->startElement('GetRecord');
        $this->record($article);
        $this->xml->endElement();
    }

    private function record(Article $article): void
    {
        $this->xml->startElement('record');
        $this->header($article);

        if (! self::isDeleted($article)) {
            $this->xml->startElement('metadata');
            $this->xml->startElement('oai_dc:dc');
            $this->xml->writeAttribute('xmlns:oai_dc', 'http://www.openarchives.org/OAI/2.0/oai_dc/');
            $this->xml->writeAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
            $this->xml->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
            $this->xml->writeAttribute('xsi:schemaLocation', 'http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd');

            foreach ($this->dublinCore->elements($article) as [$name, $value, $lang]) {
                $this->xml->startElement('dc:'.$name);

                if ($lang !== null) {
                    $this->xml->writeAttribute('xml:lang', $lang);
                }

                $this->xml->text(self::clean($value));
                $this->xml->endElement();
            }

            $this->xml->endElement();
            $this->xml->endElement();
        }

        $this->xml->endElement();
    }

    private function header(Article $article): void
    {
        $this->xml->startElement('header');

        if (self::isDeleted($article)) {
            $this->xml->writeAttribute('status', 'deleted');
        }

        $this->xml->writeElement('identifier', self::identifier($article));
        $this->xml->writeElement('datestamp', self::datestamp(
            CarbonImmutable::instance($article->updated_at ?? $article->published_at ?? CarbonImmutable::now()),
        ));

        if (! self::isDeleted($article)) {
            if ($article->subject !== null) {
                $this->xml->writeElement('setSpec', 'subject:'.$article->subject->slug);
            }

            $issue = $article->placement?->issue;

            if ($issue instanceof JournalIssue && $issue->published_at !== null) {
                $this->xml->writeElement('setSpec', 'issue:'.$issue->slug);
            }
        }

        $this->xml->endElement();
    }

    /**
     * Saytda bir marta nashr etilgan barcha maqolalar (o'chirilganlari ham — "deleted" sifatida).
     *
     * @return Builder<Article>
     */
    private function baseQuery(): Builder
    {
        return Article::withTrashed()
            ->whereNotNull('articles.published_at')
            ->whereNotNull('articles.slug');
    }

    /**
     * @return Builder<Article>
     *
     * @throws OaiException
     */
    private function filteredQuery(ResumptionToken $token): Builder
    {
        // Qiymatlar validate()'da (yoki imzolangan tokenda) tekshirilgan
        $query = $this->baseQuery();
        $from = $token->from !== null ? self::parseDate($token->from, false) : null;
        $until = $token->until !== null ? self::parseDate($token->until, true) : null;

        if ($from !== null) {
            $query->where('articles.updated_at', '>=', $from);
        }

        if ($until !== null) {
            $query->where('articles.updated_at', '<=', $until);
        }

        if ($token->set !== null) {
            [$type, $slug] = array_pad(explode(':', $token->set, 2), 2, '');

            match ($type) {
                'subject' => $query->whereHas('subject', fn (Builder $q) => $q->where('slug', $slug)),
                'issue' => $query->whereHas('placement.issue', fn (Builder $q) => $q->where('slug', $slug)->whereNotNull('published_at')),
                default => throw new OaiException('badResumptionToken', 'The value of the resumptionToken argument is invalid or expired.'),
            };
        }

        return $query;
    }

    /**
     * @throws OaiException
     */
    private function findArticle(string $identifier): Article
    {
        $prefix = 'oai:'.self::repositoryId().':article/';
        $id = str_starts_with($identifier, $prefix) ? substr($identifier, strlen($prefix)) : '';
        $article = ctype_digit($id) ? $this->baseQuery()->whereKey((int) $id)->first() : null;

        if (! $article instanceof Article) {
            throw new OaiException('idDoesNotExist', 'The value of the identifier argument is unknown or illegal in this repository.');
        }

        return $article;
    }

    /**
     * @throws OaiException
     */
    private function ensurePrefix(string $prefix): void
    {
        if ($prefix !== self::METADATA_PREFIX) {
            throw new OaiException('cannotDisseminateFormat', 'The metadata format identified by the value given for the metadataPrefix argument is not supported by the item or by the repository.');
        }
    }

    /**
     * YYYY-MM-DD yoki YYYY-MM-DDThh:mm:ssZ (UTC).
     *
     * @throws OaiException
     */
    private static function parseDate(string $value, bool $endOfDay): CarbonImmutable
    {
        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
                $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, 'UTC');

                if ($date instanceof CarbonImmutable && $date->format('Y-m-d') === $value) {
                    return $endOfDay ? $date->endOfDay() : $date;
                }
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value) === 1) {
                $date = CarbonImmutable::createFromFormat('Y-m-d\TH:i:s\Z', $value, 'UTC');

                if ($date instanceof CarbonImmutable) {
                    return $date;
                }
            }
        } catch (Throwable) {
            // pastda badArgument
        }

        throw OaiException::badArgument("Illegal date: '{$value}'.");
    }

    public static function isDeleted(Article $article): bool
    {
        return $article->trashed() || $article->status !== ArticleStatus::Published;
    }

    public static function identifier(Article $article): string
    {
        return 'oai:'.self::repositoryId().':article/'.$article->id;
    }

    public static function repositoryId(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : 'insonvajamiyat.uz';
    }

    private static function datestamp(CarbonImmutable $date): string
    {
        return $date->utc()->format('Y-m-d\TH:i:s\Z');
    }

    /** XML 1.0 da ruxsat etilmagan boshqaruv belgilari olib tashlanadi */
    private static function clean(string $text): string
    {
        return (string) preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $text);
    }
}
