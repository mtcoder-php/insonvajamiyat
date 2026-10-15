<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleStatus;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\JournalIssue;
use App\Models\User;
use App\Support\Pdf\Qpdf;
use App\Support\Pdf\TrueTypeFont;
use App\Support\PdfPageCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\FakePdf;
use Tests\TestCase;

/**
 * To'liq son PDF ni avtomatik yig'ish: muqova + maqolalar, xatcho'plar va sahifa belgilari.
 * Haqiqiy yig'ish testi serverda qpdf (11+) bo'lsa bajariladi.
 */
class IssuePdfBuildTest extends TestCase
{
    use RefreshDatabase;

    private User $layout;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        Notification::fake();
        $this->layout = User::factory()->withRole(RoleName::LayoutEditor)->createOne();
    }

    private function article(int $pages): Article
    {
        $article = Article::factory()->withAuthors()->status(ArticleStatus::InProduction)->createOne([
            'submitter_id' => User::factory()->author()->createOne()->id,
            'layout_editor_id' => $this->layout->id,
        ]);
        $article->forceFill(['accepted_at' => now()->subWeek()])->save();

        $this->actingAs($this->layout)
            ->post(route('admin.production.final-pdf', $article->uuid), ['pdf' => FakePdf::upload($pages)])
            ->assertSessionHasNoErrors();

        return $article;
    }

    private function issue(): JournalIssue
    {
        return JournalIssue::factory()->createOne(['year' => 2026, 'number' => 7, 'slug' => '2026-7']);
    }

    public function test_issue_pdf_is_built_with_cover_bookmarks_and_page_labels(): void
    {
        if (! Qpdf::available()) {
            $this->markTestSkipped('qpdf (11+) o\'rnatilmagan: sudo apt install qpdf');
        }

        [$a, $b] = [$this->article(3), $this->article(2)];
        $issue = $this->issue();

        // Testda public/ dagi umumiy muqovaga tayanmaymiz — sonning o'z muqovasi
        $cover = UploadedFile::fake()->image('cover.jpg', 600, 850)->store('issues/2026-7', 'public');
        $issue->forceFill(['cover_image_path' => $cover])->save();

        $this->actingAs($this->layout)
            ->post(route('admin.issues.articles.store', $issue->slug), ['article_ids' => [$a->id, $b->id]])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->layout)
            ->get(route('admin.issues.show', $issue->slug))
            ->assertInertia(fn (Assert $page) => $page
                ->where('issue.pdfBuild.canBuild', true)
                ->where('issue.pdfBuild.status', null)
            );

        // Navbat testda sinxron — so'rovdan keyin fayl tayyor
        $this->actingAs($this->layout)
            ->post(route('admin.issues.pdf.build', $issue->slug))
            ->assertSessionHasNoErrors();

        $issue->refresh();
        $this->assertSame('done', $issue->pdf_status, (string) $issue->pdf_error);
        $this->assertTrue($issue->pdf_auto);
        $this->assertSame(8, $issue->pdf_pages); // muqova + jurnal ma'lumotlari + 3 + 2 + mundarija (avtomatik)
        Storage::disk('public')->assertExists((string) $issue->full_pdf_path);

        // Betlar avtomatik: 1 — muqova, 2 — jurnal ma'lumotlari, maqolalar 3-betdan
        $this->assertDatabaseHas('issue_articles', ['article_id' => $a->id, 'page_from' => 3, 'page_to' => 5]);
        $this->assertDatabaseHas('issue_articles', ['article_id' => $b->id, 'page_from' => 6, 'page_to' => 7]);

        $file = Storage::disk('public')->path((string) $issue->full_pdf_path);
        $this->assertSame(8, PdfPageCounter::count($file));

        $json = json_decode(Process::run([Qpdf::binary(), '--json=2', '--json-key=outlines', '--json-key=pagelabels', $file])->output(), true);
        $this->assertIsArray($json);
        $this->assertSame('Muqova', $json['outlines'][0]['title']);
        $this->assertSame('Jurnal haqida', $json['outlines'][1]['title']);
        $this->assertSame(2, $json['outlines'][1]['destpageposfrom1']);
        $this->assertSame(3, $json['outlines'][2]['destpageposfrom1']);
        $this->assertSame('Mundarija', $json['outlines'][array_key_last($json['outlines'])]['title']);
        $this->assertSame(8, $json['outlines'][array_key_last($json['outlines'])]['destpageposfrom1']);
        // Bet belgilari bosilgan raqam bilan bir xil: 1, 2, 3 …
        $this->assertCount(1, $json['pagelabels']);
        $this->assertSame(['/S' => '/D', '/St' => 1], $json['pagelabels'][0]['label']);

        // Ichki betlarga ramka, bant (jurnal nomi + bo'lim), shior va bet raqami bosilgan, muqovaga — yo'q
        $qdf = Storage::disk('public')->path('check-qdf.pdf');
        Process::run([Qpdf::binary(), '--qdf', '--object-streams=disable', $file, $qdf]);
        $raw = (string) file_get_contents($qdf);
        // Matn Identity-H (glyph raqamlari) bilan yoziladi — kutilgan glyph ketma-ketligi qidiriladi
        $this->assertStringContainsString('['.$this->glyphs('INSON VA JAMIYAT', 'LiberationSerif-Regular').'] TJ', $raw);
        $this->assertStringContainsString('['.$this->glyphs('MAQOLA DAVOMI', 'LiberationSans-Regular').'] TJ', $raw);
        $this->assertStringContainsString('['.$this->glyphs('MUNDARIJA', 'LiberationSans-Regular').'] TJ', $raw);
        $this->assertStringContainsString('['.$this->glyphs('Ilm – jamiyat taraqqiyotining eng muhim omili', 'LiberationSans-Italic').'] TJ', $raw);
        $this->assertStringContainsString('['.$this->glyphs('8', 'LiberationSans-Bold').'] TJ', $raw);
        // Avtomatik mundarijada maqola sarlavhasi
        $this->assertStringContainsString(substr($this->glyphs((string) strtok($a->title, ' '), 'LiberationSerif-Bold'), 0, -1), $raw);
        // Muqovaga bet raqami bosilmaydi
        $this->assertStringNotContainsString('['.$this->glyphs('1', 'LiberationSans-Bold').'] TJ', $raw);
        $this->assertDatabaseHas('audit_logs', ['event' => 'issue.pdf_built']);

        // Maqola PDF i yangilansa — eskirgan
        $this->travel(1)->minutes();
        $this->actingAs($this->layout)
            ->post(route('admin.production.final-pdf', $a->uuid), ['pdf' => FakePdf::upload(3)]);

        $this->actingAs($this->layout)
            ->get(route('admin.issues.show', $issue->slug))
            ->assertInertia(fn (Assert $page) => $page->where('issue.pdfBuild.stale', true));
    }

    public function test_uploaded_info_page_uses_only_first_page(): void
    {
        if (! Qpdf::available()) {
            $this->markTestSkipped('qpdf (11+) o\'rnatilmagan: sudo apt install qpdf');
        }

        $article = $this->article(2);
        $issue = $this->issue();

        $this->actingAs($this->layout)
            ->post(route('admin.issues.articles.store', $issue->slug), ['article_ids' => [$article->id]])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->layout)
            ->post(route('admin.issues.files.store', $issue->slug), ['type' => 'info', 'file' => FakePdf::upload(3, 'malumot.pdf')])
            ->assertSessionHasNoErrors();
        $this->assertNotNull($issue->refresh()->info_file_path);

        $this->actingAs($this->layout)->post(route('admin.issues.pdf.build', $issue->slug))->assertSessionHasNoErrors();

        // muqova + 1 bet (yuklangan 3 betdan faqat birinchisi) + 2 + mundarija
        $this->assertSame(5, $issue->refresh()->pdf_pages, (string) $issue->pdf_error);
        $this->assertDatabaseHas('issue_articles', ['article_id' => $article->id, 'page_from' => 3, 'page_to' => 4]);
    }

    public function test_build_requires_consecutive_pages_from_third_page(): void
    {
        $article = $this->article(2);
        $issue = $this->issue();

        $this->actingAs($this->layout)
            ->post(route('admin.issues.articles.store', $issue->slug), ['article_ids' => [$article->id]]);

        // Qo'lda noto'g'ri bet — yig'ib bo'lmaydi, avtomatik hisoblash tuzatadi
        $issue->articles()->updateExistingPivot($article->id, ['page_from' => 10, 'page_to' => 11]);

        $this->actingAs($this->layout)
            ->get(route('admin.issues.show', $issue->slug))
            ->assertInertia(fn (Assert $page) => $page->where('issue.pdfBuild.canBuild', false));

        $this->actingAs($this->layout)
            ->post(route('admin.issues.articles.paginate', $issue->slug))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('issue_articles', ['article_id' => $article->id, 'page_from' => 3, 'page_to' => 4]);
    }

    public function test_build_requires_articles_with_final_pdf_and_permission(): void
    {
        $issue = $this->issue();

        $this->actingAs($this->layout)
            ->post(route('admin.issues.pdf.build', $issue->slug))
            ->assertSessionHasErrors('pdf');

        $this->assertNull($issue->refresh()->pdf_status);

        $this->actingAs(User::factory()->withRole(RoleName::Reviewer)->createOne())
            ->post(route('admin.issues.pdf.build', $issue->slug))
            ->assertForbidden();
    }

    public function test_manual_upload_resets_auto_flag(): void
    {
        $issue = $this->issue();
        $issue->forceFill(['pdf_auto' => true, 'pdf_status' => 'done', 'pdf_pages' => 10, 'pdf_built_at' => now()])->save();

        $this->actingAs($this->layout)
            ->post(route('admin.issues.files.store', $issue->slug), ['type' => 'pdf', 'file' => FakePdf::upload(4, 'son.pdf')])
            ->assertSessionHasNoErrors();

        $issue->refresh();
        $this->assertFalse($issue->pdf_auto);
        $this->assertNull($issue->pdf_status);
        $this->assertSame(4, $issue->pdf_pages);
    }

    /** Matnning PDF dagi ko'rinishi: <glyph raqamlari hex> (SimplePdf, Identity-H) */
    private function glyphs(string $text, string $font): string
    {
        $ttf = TrueTypeFont::load(resource_path("fonts/liberation/{$font}.ttf"));

        return '<'.implode('', array_map(fn (string $char): string => sprintf('%04X', $ttf->glyph(mb_ord($char))), mb_str_split($text))).'>';
    }
}
