<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleStatus;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\JournalIssue;
use App\Models\User;
use App\Support\Pdf\Qpdf;
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
        $this->assertSame(6, $issue->pdf_pages); // muqova + 3 + 2
        Storage::disk('public')->assertExists((string) $issue->full_pdf_path);

        $file = Storage::disk('public')->path((string) $issue->full_pdf_path);
        $this->assertSame(6, PdfPageCounter::count($file));

        $json = json_decode(Process::run([Qpdf::binary(), '--json=2', '--json-key=outlines', '--json-key=pagelabels', $file])->output(), true);
        $this->assertIsArray($json);
        $this->assertSame('Muqova', $json['outlines'][0]['title']);
        $this->assertSame(2, $json['outlines'][1]['destpageposfrom1']);
        $this->assertSame(['/S' => '/D', '/St' => 1], $json['pagelabels'][1]['label']);
        $this->assertSame(4, $json['pagelabels'][2]['label']['/St']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'issue.pdf_built']);

        // Maqola PDF i yangilansa — eskirgan
        $this->travel(1)->minutes();
        $this->actingAs($this->layout)
            ->post(route('admin.production.final-pdf', $a->uuid), ['pdf' => FakePdf::upload(3)]);

        $this->actingAs($this->layout)
            ->get(route('admin.issues.show', $issue->slug))
            ->assertInertia(fn (Assert $page) => $page->where('issue.pdfBuild.stale', true));
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
}
