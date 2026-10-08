<?php

namespace Tests\Feature\Indexing;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\JournalIssue;
use App\Models\Subject;
use App\Services\Indexing\OaiPmh\OaiPmhServer;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * OAI-PMH 2.0 endpoint (/oai): barcha verb'lar, xatolar, sahifalash, o'chirilgan yozuvlar.
 * Har bir javob rasmiy OAI-PMH.xsd sxemasi bo'yicha tekshiriladi.
 */
class OaiPmhTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://insonvajamiyat.uz', 'journal.name' => 'Inson va Jamiyat', 'journal.eissn' => '2992-1234']);
    }

    private function oai(string $query): DOMXPath
    {
        $response = $this->get('/oai?'.$query);
        $response->assertOk()->assertHeader('Content-Type', 'text/xml; charset=UTF-8');

        return $this->xpath($response);
    }

    private function xpath(TestResponse $response): DOMXPath
    {
        $doc = new DOMDocument;
        $this->assertTrue($doc->loadXML((string) $response->getContent()), 'XML noto\'g\'ri');

        libxml_use_internal_errors(true);
        $valid = $doc->schemaValidate(base_path('tests/Fixtures/oai/OAI-PMH.xsd'));
        $errors = array_map(fn ($e) => trim($e->message), libxml_get_errors());
        libxml_clear_errors();
        $this->assertTrue($valid, "OAI-PMH.xsd bo'yicha xato:\n".implode("\n", $errors));

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('o', 'http://www.openarchives.org/OAI/2.0/');
        $xpath->registerNamespace('dc', 'http://purl.org/dc/elements/1.1/');
        $xpath->registerNamespace('oai_dc', 'http://www.openarchives.org/OAI/2.0/oai_dc/');

        return $xpath;
    }

    private function article(array $attributes = []): Article
    {
        $article = Article::factory()->published()->createOne([
            'title' => ['uz' => 'Buxoro hunarmandchiligi', 'en' => 'Crafts of Bukhara'],
            'abstract' => ['uz' => 'Maqolada <b>hunarmandchilik</b> tahlil qilinadi.', 'en' => 'The article analyses crafts.'],
            'keywords' => ['uz' => ['tarix', 'Buxoro']],
            'doi' => '10.5281/ivj.2026.1',
            ...$attributes,
        ]);
        ArticleAuthor::factory()->for($article)->create(['last_name' => 'Karimov', 'first_name' => 'Olim', 'middle_name' => null, 'sort_order' => 1]);

        return $article;
    }

    private function error(DOMXPath $xpath): ?string
    {
        $node = $xpath->query('/o:OAI-PMH/o:error')?->item(0);

        return $node?->attributes?->getNamedItem('code')?->nodeValue;
    }

    public function test_identify_and_metadata_formats(): void
    {
        $this->article();
        $xpath = $this->oai('verb=Identify');

        $this->assertSame('Inson va Jamiyat', $xpath->evaluate('string(//o:Identify/o:repositoryName)'));
        $this->assertSame(url('/oai'), $xpath->evaluate('string(//o:Identify/o:baseURL)'));
        $this->assertSame('2.0', $xpath->evaluate('string(//o:Identify/o:protocolVersion)'));
        $this->assertSame('transient', $xpath->evaluate('string(//o:Identify/o:deletedRecord)'));
        $this->assertSame('YYYY-MM-DDThh:mm:ssZ', $xpath->evaluate('string(//o:Identify/o:granularity)'));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $xpath->evaluate('string(//o:Identify/o:earliestDatestamp)'));

        $formats = $this->oai('verb=ListMetadataFormats');
        $this->assertSame('oai_dc', $formats->evaluate('string(//o:metadataFormat/o:metadataPrefix)'));
    }

    public function test_list_records_returns_dublin_core(): void
    {
        $subject = Subject::factory()->createOne(['slug' => 'history', 'name' => ['uz' => 'Tarix', 'en' => 'History']]);
        $issue = JournalIssue::factory()->published()->createOne(['slug' => '2026-1', 'year' => 2026, 'volume' => 2, 'number' => 1]);
        $article = $this->article(['subject_id' => $subject->id]);
        $issue->articles()->attach($article->id, ['position' => 1, 'page_from' => 5, 'page_to' => 14]);
        Article::factory()->status(ArticleStatus::UnderReview)->createOne(); // nashr etilmagan — chiqmaydi

        $xpath = $this->oai('verb=ListRecords&metadataPrefix=oai_dc');
        $dc = fn (string $name, ?string $lang = null): array => array_map(
            fn ($node) => $node->textContent,
            iterator_to_array($xpath->query('//oai_dc:dc/dc:'.$name.($lang ? "[@xml:lang='{$lang}']" : '')) ?: []),
        );

        $this->assertSame(1, $xpath->query('//o:record')?->length);
        $this->assertSame('oai:insonvajamiyat.uz:article/'.$article->id, $xpath->evaluate('string(//o:header/o:identifier)'));
        $this->assertSame(['subject:history', 'issue:2026-1'], array_map(fn ($n) => $n->textContent, iterator_to_array($xpath->query('//o:header/o:setSpec') ?: [])));

        $this->assertSame(['Buxoro hunarmandchiligi'], $dc('title', 'uz'));
        $this->assertSame(['Crafts of Bukhara'], $dc('title', 'en'));
        $this->assertSame(['Karimov, Olim'], $dc('creator'));
        $this->assertSame(['Maqolada hunarmandchilik tahlil qilinadi.'], $dc('description', 'uz'));
        $this->assertContains('Tarix', $dc('subject', 'uz'));
        $this->assertContains('Buxoro', $dc('subject', 'uz'));
        $this->assertContains('https://doi.org/10.5281/ivj.2026.1', $dc('identifier'));
        $this->assertContains(route('articles.show', $article->slug), $dc('identifier'));
        $this->assertContains('Inson va Jamiyat; Vol. 2, No. 1 (2026); 5-14', $dc('source'));
        $this->assertContains('ISSN 2992-1234', $dc('source'));
        $this->assertSame(['uzb'], $dc('language'));
        $this->assertSame([$article->published_at?->format('Y-m-d')], $dc('date'));
        $this->assertContains('info:eu-repo/semantics/article', $dc('type'));

        // Yo'nalish va son to'plamlari
        $this->assertSame(1, $this->oai('verb=ListIdentifiers&metadataPrefix=oai_dc&set=subject:history')->query('//o:header')?->length);
        $this->assertSame(1, $this->oai('verb=ListIdentifiers&metadataPrefix=oai_dc&set=issue:2026-1')->query('//o:header')?->length);
        $this->assertSame('noRecordsMatch', $this->error($this->oai('verb=ListIdentifiers&metadataPrefix=oai_dc&set=subject:philosophy')));

        $sets = $this->oai('verb=ListSets');
        $specs = array_map(fn ($n) => $n->textContent, iterator_to_array($sets->query('//o:set/o:setSpec') ?: []));
        $this->assertContains('subject:history', $specs);
        $this->assertContains('issue:2026-1', $specs);

        // GetRecord
        $record = $this->oai('verb=GetRecord&metadataPrefix=oai_dc&identifier=oai:insonvajamiyat.uz:article/'.$article->id);
        $this->assertSame('Buxoro hunarmandchiligi', $record->evaluate("string(//dc:title[@xml:lang='uz'])"));
    }

    public function test_paging_with_resumption_token(): void
    {
        foreach (range(1, OaiPmhServer::PAGE_SIZE + 5) as $i) {
            Article::factory()->published()->createOne(['doi' => null]);
        }

        $first = $this->oai('verb=ListIdentifiers&metadataPrefix=oai_dc');
        $this->assertSame(OaiPmhServer::PAGE_SIZE, $first->query('//o:header')?->length);
        $token = $first->evaluate('string(//o:resumptionToken)');
        $this->assertSame((string) (OaiPmhServer::PAGE_SIZE + 5), $first->evaluate('string(//o:resumptionToken/@completeListSize)'));
        $this->assertNotSame('', $token);

        $second = $this->oai('verb=ListIdentifiers&resumptionToken='.urlencode($token));
        $this->assertSame(5, $second->query('//o:header')?->length);
        $this->assertSame('', $second->evaluate('string(//o:resumptionToken)'), 'Oxirgi sahifada bo\'sh token');
        $this->assertSame((string) OaiPmhServer::PAGE_SIZE, $second->evaluate('string(//o:resumptionToken/@cursor)'));

        // Takrorsiz va to'liq
        $ids = array_map(fn ($n) => $n->textContent, [
            ...iterator_to_array($first->query('//o:header/o:identifier') ?: []),
            ...iterator_to_array($second->query('//o:header/o:identifier') ?: []),
        ]);
        $this->assertCount(OaiPmhServer::PAGE_SIZE + 5, array_unique($ids));

        // Soxta token
        $this->assertSame('badResumptionToken', $this->error($this->oai('verb=ListIdentifiers&resumptionToken='.urlencode(substr($token, 0, -2).'xx'))));
        $this->assertSame('badArgument', $this->error($this->oai('verb=ListIdentifiers&metadataPrefix=oai_dc&resumptionToken='.urlencode($token))));
    }

    public function test_date_filters_and_deleted_records(): void
    {
        $old = $this->article(['doi' => null]);
        $old->forceFill(['updated_at' => '2026-01-10 08:00:00'])->saveQuietly();
        $new = $this->article(['doi' => null]);
        $new->forceFill(['updated_at' => '2026-05-20 12:30:00'])->saveQuietly();

        $this->assertSame(1, $this->oai('verb=ListIdentifiers&metadataPrefix=oai_dc&from=2026-05-01')->query('//o:header')?->length);
        $this->assertSame(1, $this->oai('verb=ListIdentifiers&metadataPrefix=oai_dc&until=2026-01-10')->query('//o:header')?->length);
        $this->assertSame(2, $this->oai('verb=ListIdentifiers&metadataPrefix=oai_dc&from=2026-01-10T08:00:00Z&until=2026-05-20T12:30:00Z')->query('//o:header')?->length);

        foreach (['from=2026-13-01', 'from=2026-05-01&until=2026-01-01', 'from=2026-01-01&until=2026-05-01T00:00:00Z', 'from=yesterday'] as $bad) {
            $this->assertSame('badArgument', $this->error($this->oai('verb=ListIdentifiers&metadataPrefix=oai_dc&'.$bad)), $bad);
        }

        // Saytdan olib tashlangan maqola — status="deleted", metadata'siz
        $old->delete();
        $xpath = $this->oai('verb=GetRecord&metadataPrefix=oai_dc&identifier=oai:insonvajamiyat.uz:article/'.$old->id);
        $this->assertSame('deleted', $xpath->evaluate('string(//o:header/@status)'));
        $this->assertSame(0, $xpath->query('//o:metadata')?->length);
    }

    public function test_protocol_errors(): void
    {
        $this->article();

        $cases = [
            '' => 'badVerb',
            'verb=Harvest' => 'badVerb',
            'verb=ListRecords' => 'badArgument',
            'verb=ListRecords&metadataPrefix=oai_dc&foo=bar' => 'badArgument',
            'verb=ListRecords&metadataPrefix=oai_dc&metadataPrefix=oai_dc' => 'badArgument',
            'verb=ListRecords&metadataPrefix=marc21' => 'cannotDisseminateFormat',
            'verb=GetRecord&metadataPrefix=oai_dc&identifier=oai:insonvajamiyat.uz:article/999999' => 'idDoesNotExist',
            'verb=GetRecord&metadataPrefix=oai_dc&identifier=article/1' => 'idDoesNotExist',
            'verb=ListMetadataFormats&identifier=oai:boshqa.uz:article/1' => 'idDoesNotExist',
            'verb=ListSets&resumptionToken=abc' => 'badResumptionToken',
            'verb=ListRecords&metadataPrefix=oai_dc&set=journal:x' => 'badArgument',
        ];

        foreach ($cases as $query => $code) {
            $xpath = $this->oai($query);
            $this->assertSame($code, $this->error($xpath), $query);

            // badVerb / badArgument'da <request> atributsiz
            if (in_array($code, ['badVerb', 'badArgument'], true)) {
                $this->assertSame(0, $xpath->query('//o:request/@*')?->length, $query);
            }
        }

        // POST ham qo'llab-quvvatlanadi (CSRF'siz)
        $response = $this->call('POST', '/oai', [], [], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'], 'verb=Identify');
        $response->assertOk();
        $this->assertSame('2.0', $this->xpath($response)->evaluate('string(//o:protocolVersion)'));
    }
}
