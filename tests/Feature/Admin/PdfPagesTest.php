<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleStatus;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\IssueArticle;
use App\Models\JournalIssue;
use App\Models\User;
use App\Support\PdfPageCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakePdf;
use Tests\TestCase;

/**
 * Betlar soni yakuniy PDF dan olinadi, sondagi sahifalar avtomatik hisoblanadi.
 */
class PdfPagesTest extends TestCase
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

    private function article(): Article
    {
        $article = Article::factory()->withAuthors()->status(ArticleStatus::InProduction)->createOne([
            'submitter_id' => User::factory()->author()->createOne()->id,
            'layout_editor_id' => $this->layout->id,
        ]);
        $article->forceFill(['accepted_at' => now()->subWeek()])->save();

        return $article;
    }

    private function uploadPdf(Article $article, int $pages): void
    {
        $this->actingAs($this->layout)
            ->post(route('admin.production.final-pdf', $article->uuid), ['pdf' => FakePdf::upload($pages)])
            ->assertSessionHasNoErrors();
    }

    /**
     * @return array<int, string>
     */
    private function pages(JournalIssue $issue): array
    {
        return IssueArticle::query()->where('journal_issue_id', $issue->id)->orderBy('position')->get()
            ->map(fn (IssueArticle $p): string => $p->article_id.':'.$p->pages())
            ->all();
    }

    public function test_counter_reads_page_count(): void
    {
        $this->assertSame(1, PdfPageCounter::countContent(FakePdf::content(1)));
        $this->assertSame(17, PdfPageCounter::countContent(FakePdf::content(17)));
        $this->assertNull(PdfPageCounter::countContent('bu pdf emas'));
        $this->assertNull(PdfPageCounter::count('/yo-q/fayl.pdf'));
    }

    public function test_final_pdf_sets_article_pages_and_metadata_computes_last_page(): void
    {
        $article = $this->article();
        $this->uploadPdf($article, 9);

        $article->refresh();
        $this->assertSame(9, $article->pages_count);
        $this->assertSame(9, $article->files()->latest('id')->firstOrFail()->page_count);

        $issue = JournalIssue::factory()->createOne(['year' => 2026, 'number' => 5]);

        // Oxirgi bet qo'lda noto'g'ri yozilsa ham PDF bo'yicha hisoblanadi
        $this->actingAs($this->layout)
            ->put(route('admin.production.metadata', $article->uuid), [
                'doi' => '10.5281/insonvajamiyat.2026.0001',
                'udc' => '94(575.1)',
                'issue_id' => $issue->id,
                'page_from' => 21,
                'page_to' => 22,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('21–29', $article->refresh()->placement?->pages());
        $this->assertSame(9, $article->pages_count);
    }

    public function test_issue_pages_are_recalculated_automatically(): void
    {
        [$a, $b, $c] = [$this->article(), $this->article(), $this->article()];
        $this->uploadPdf($a, 8);
        $this->uploadPdf($b, 6);

        $issue = JournalIssue::factory()->createOne(['year' => 2026, 'number' => 6, 'slug' => '2026-6']);

        $this->actingAs($this->layout)
            ->post(route('admin.issues.articles.store', $issue->slug), ['article_ids' => [$a->id, $b->id, $c->id]])
            ->assertSessionHasNoErrors();

        // C ning PDF i hali yo'q — avtomatik hisoblanmaydi
        $this->assertSame(["{$a->id}:", "{$b->id}:", "{$c->id}:"], $this->pages($issue));

        // C ning PDF i yuklandi — hammasi 1-betdan ketma-ket
        $this->uploadPdf($c, 10);
        $this->assertSame(["{$a->id}:1–8", "{$b->id}:9–14", "{$c->id}:15–24"], $this->pages($issue));

        // Tartib o'zgardi — qayta hisoblanadi
        $this->actingAs($this->layout)
            ->put(route('admin.issues.articles.reorder', $issue->slug), ['order' => [$c->id, $a->id, $b->id]])
            ->assertSessionHasNoErrors();
        $this->assertSame(["{$c->id}:1–10", "{$a->id}:11–18", "{$b->id}:19–24"], $this->pages($issue));

        // Yangi versiya (12 bet) — keyingilar suriladi
        $this->uploadPdf($c, 12);
        $this->assertSame(["{$c->id}:1–12", "{$a->id}:13–20", "{$b->id}:21–26"], $this->pages($issue));

        // Maqola chiqarildi — bo'shliq qolmaydi
        $this->actingAs($this->layout)
            ->delete(route('admin.issues.articles.destroy', [$issue->slug, $a->uuid]))
            ->assertSessionHasNoErrors();
        $this->assertSame(["{$c->id}:1–12", "{$b->id}:13–18"], $this->pages($issue));
    }
}
