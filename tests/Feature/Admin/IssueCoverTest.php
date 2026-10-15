<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\JournalIssue;
use App\Models\User;
use App\Services\Issues\IssueCoverService;
use App\Services\Issues\IssuePdfBuilder;
use App\Support\Pdf\TrueTypeFont;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Admin → Jurnallar → «Umumiy muqova»: bitta rasm barcha sonlar uchun, ustiga son raqami yoziladi.
 */
class IssueCoverTest extends TestCase
{
    use RefreshDatabase;

    private User $layout;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->layout = User::factory()->withRole(RoleName::LayoutEditor)->createOne();
    }

    public function test_common_cover_is_uploaded_configured_and_used_for_every_issue(): void
    {
        $issue = JournalIssue::factory()->createOne(['year' => 2026, 'number' => 4]);

        $this->actingAs($this->layout)
            ->post(route('admin.issues.cover.store'), ['file' => UploadedFile::fake()->image('muqova.png', 1240, 1754)])
            ->assertSessionHasNoErrors();

        $covers = app(IssueCoverService::class);
        $path = (string) $covers->path();
        Storage::disk('public')->assertExists($path);

        $this->actingAs($this->layout)
            ->get(route('admin.issues.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('cover.custom', true)
                ->where('cover.stamp', true)
                ->where('cover.position', 'bottom')
                ->where('cover.color', 'gold')
            );

        $this->actingAs($this->layout)
            ->put(route('admin.issues.cover.update'), ['stamp' => true, 'position' => 'top', 'color' => 'white'])
            ->assertSessionHasNoErrors();

        $this->assertSame('top', $covers->position());
        $this->assertSame('white', $covers->color());

        // Son PDF muqovasi: umumiy rasm + "№ 4 | 2026" yozuvi
        $pdf = app(IssuePdfBuilder::class)->coverPdf($issue);
        $serif = TrueTypeFont::load(resource_path('fonts/liberation/LiberationSerif-Bold.ttf'));
        $glyphs = implode('', array_map(fn (string $c): string => sprintf('%04X', $serif->glyph(mb_ord($c))), mb_str_split('№ 4   |   2026')));
        preg_match_all('/stream\n(.*?)\nendstream/s', $pdf, $streams);
        $content = implode("\n", array_map(fn (string $s): string => (string) @gzuncompress($s), $streams[1]));
        $this->assertStringContainsString('<'.$glyphs.'>', $content);
        $this->assertStringContainsString('/Subtype /Image', $pdf);

        // Namuna PDF
        $this->actingAs($this->layout)
            ->get(route('admin.issues.cover.preview'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // O'chirish — fayl ham o'chadi, muqova avtomatik chiziladi
        $this->actingAs($this->layout)->delete(route('admin.issues.cover.destroy'))->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($path);
        $this->assertNull($covers->path());
    }

    public function test_cover_requires_issue_permission_and_valid_image(): void
    {
        $this->actingAs($this->layout)
            ->post(route('admin.issues.cover.store'), ['file' => UploadedFile::fake()->create('muqova.pdf', 100, 'application/pdf')])
            ->assertSessionHasErrors('file');

        $this->actingAs(User::factory()->withRole(RoleName::Reviewer)->createOne())
            ->post(route('admin.issues.cover.store'), ['file' => UploadedFile::fake()->image('m.png', 1240, 1754)])
            ->assertForbidden();
    }
}
