<?php

namespace Tests\Feature\Indexing;

use App\Enums\ArticleStatus;
use App\Enums\AuditEvent;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\JournalIssue;
use App\Models\User;
use App\Services\Indexing\CrossrefDeposit;
use Carbon\CarbonImmutable;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Crossref DOI deposit XML (schema 5.4.0) — chop etilgan jurnal soni uchun.
 */
class CrossrefDepositTest extends TestCase
{
    use RefreshDatabase;

    private JournalIssue $issue;

    private Article $withDoi;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'journal.name' => 'Inson va Jamiyat',
            'journal.issn' => '2181-0001',
            'journal.eissn' => '2992-123X',
            'journal.contact.email' => 'info@insonvajamiyat.uz',
        ]);

        $this->issue = JournalIssue::factory()->published(CarbonImmutable::parse('2026-03-15'))->createOne([
            'slug' => '2026-1', 'year' => 2026, 'volume' => 2, 'number' => 1, 'doi' => '10.5281/ivj.2026.i1',
        ]);

        $this->withDoi = Article::factory()->published(CarbonImmutable::parse('2026-03-15'))->createOne([
            'language' => 'uz',
            'title' => ['uz' => 'Buxoro <i>hunarmandchiligi</i> & an\'analar', 'en' => 'Crafts'],
            'abstract' => ['uz' => 'O\'zbekcha annotatsiya.', 'en' => 'English abstract.'],
            'doi' => '10.5281/ivj.2026.1',
        ]);
        ArticleAuthor::factory()->for($this->withDoi)->create([
            'last_name' => 'Karimov', 'first_name' => 'Olim', 'middle_name' => null, 'sort_order' => 1,
            'organization' => "O'zbekiston Milliy universiteti", 'orcid' => '0000-0002-1825-0097',
        ]);
        ArticleAuthor::factory()->for($this->withDoi)->create([
            'last_name' => 'Rahimova', 'first_name' => 'Dilnoza', 'middle_name' => null, 'sort_order' => 2,
            'organization' => null, 'orcid' => null,
        ]);

        $withoutDoi = Article::factory()->published()->createOne(['doi' => null]);

        $this->issue->articles()->attach($this->withDoi->id, ['position' => 1, 'page_from' => 5, 'page_to' => 14]);
        $this->issue->articles()->attach($withoutDoi->id, ['position' => 2, 'page_from' => 15, 'page_to' => 20]);
    }

    private function xpath(string $xml): DOMXPath
    {
        $doc = new DOMDocument;
        $this->assertTrue($doc->loadXML($xml));
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('c', 'http://www.crossref.org/schema/5.4.0');
        $xpath->registerNamespace('jats', 'http://www.ncbi.nlm.nih.gov/JATS1');

        return $xpath;
    }

    public function test_deposit_xml_contains_issue_and_articles_with_doi(): void
    {
        $x = $this->xpath(app(CrossrefDeposit::class)->build($this->issue, CarbonImmutable::parse('2026-10-08 12:00:00')));

        $this->assertSame('5.4.0', $x->evaluate('string(/c:doi_batch/@version)'));
        $this->assertSame('ivj-2026-1-20261008120000', $x->evaluate('string(//c:head/c:doi_batch_id)'));
        $this->assertSame('info@insonvajamiyat.uz', $x->evaluate('string(//c:depositor/c:email_address)'));
        $this->assertSame('Inson va Jamiyat', $x->evaluate('string(//c:journal_metadata/c:full_title)'));
        $this->assertSame('2181-0001', $x->evaluate("string(//c:issn[@media_type='print'])"));
        $this->assertSame('2992-123X', $x->evaluate("string(//c:issn[@media_type='electronic'])"));

        // Son
        $this->assertSame('2', $x->evaluate('string(//c:journal_issue/c:journal_volume/c:volume)'));
        $this->assertSame('1', $x->evaluate('string(//c:journal_issue/c:issue)'));
        $this->assertSame('10.5281/ivj.2026.i1', $x->evaluate('string(//c:journal_issue/c:doi_data/c:doi)'));
        $this->assertSame('2026', $x->evaluate('string(//c:journal_issue/c:publication_date/c:year)'));

        // Faqat DOI'li maqola
        $this->assertSame(1, $x->query('//c:journal_article')?->length);
        $this->assertSame('Buxoro hunarmandchiligi & an\'analar', $x->evaluate('string(//c:journal_article/c:titles/c:title)'));
        $this->assertSame('first', $x->evaluate('string(//c:person_name[1]/@sequence)'));
        $this->assertSame('additional', $x->evaluate('string(//c:person_name[2]/@sequence)'));
        $this->assertSame('Olim', $x->evaluate('string(//c:person_name[1]/c:given_name)'));
        $this->assertSame('Karimov', $x->evaluate('string(//c:person_name[1]/c:surname)'));
        $this->assertSame("O'zbekiston Milliy universiteti", $x->evaluate('string(//c:person_name[1]/c:affiliations/c:institution/c:institution_name)'));
        $this->assertSame('https://orcid.org/0000-0002-1825-0097', $x->evaluate('string(//c:person_name[1]/c:ORCID)'));
        $this->assertSame(0, $x->query('//c:person_name[2]/c:ORCID')?->length);
        $this->assertSame('English abstract.', $x->evaluate("string(//jats:abstract[@xml:lang='en']/jats:p)"));
        $this->assertSame('5', $x->evaluate('string(//c:journal_article/c:pages/c:first_page)'));
        $this->assertSame('14', $x->evaluate('string(//c:journal_article/c:pages/c:last_page)'));
        $this->assertSame('10.5281/ivj.2026.1', $x->evaluate('string(//c:journal_article/c:doi_data/c:doi)'));
        $this->assertSame(route('articles.show', $this->withDoi->slug), $x->evaluate('string(//c:journal_article/c:doi_data/c:resource)'));

        // Crossref sxemasidagi elementlar tartibi
        $order = array_map(fn ($n) => $n->localName, iterator_to_array($x->query('//c:journal_article/*') ?: []));
        $this->assertSame(['titles', 'contributors', 'abstract', 'abstract', 'publication_date', 'pages', 'doi_data'], $order);
    }

    public function test_admin_downloads_xml_from_published_issue(): void
    {
        $chief = User::factory()->withRole(RoleName::ChiefEditor)->createOne();

        $this->actingAs($chief)
            ->get(route('admin.issues.show', $this->issue->slug))
            ->assertInertia(fn (Assert $page) => $page
                ->where('issue.crossref.count', 1)
                ->where('issue.crossref.total', 2)
                ->where('issue.crossref.url', route('admin.issues.crossref', $this->issue->slug))
            );

        $response = $this->get(route('admin.issues.crossref', $this->issue->slug));
        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="crossref-2026-1.xml"');
        $this->assertStringContainsString('<doi>10.5281/ivj.2026.1</doi>', (string) $response->getContent());
        $this->assertDatabaseHas('audit_logs', ['event' => AuditEvent::ReportExported->value, 'user_id' => $chief->id]);

        // Qoralama son — Crossref'ga hali erta
        $draft = JournalIssue::factory()->createOne(['slug' => '2026-9']);
        $this->get(route('admin.issues.crossref', $draft->slug))->assertNotFound();

        // DOI'li maqola yo'q — xabar bilan qaytadi
        $this->withDoi->forceFill(['doi' => null])->saveQuietly();
        $this->from(route('admin.issues.show', $this->issue->slug))
            ->get(route('admin.issues.crossref', $this->issue->slug))
            ->assertRedirect(route('admin.issues.show', $this->issue->slug));

        // Muallif admin paneliga kira olmaydi
        $this->actingAs(User::factory()->author()->createOne())
            ->get(route('admin.issues.crossref', $this->issue->slug))
            ->assertForbidden();
    }

    public function test_unpublished_articles_are_not_deposited(): void
    {
        $this->withDoi->forceFill(['status' => ArticleStatus::InProduction])->saveQuietly();

        $this->assertCount(0, app(CrossrefDeposit::class)->articles($this->issue));
    }
}
