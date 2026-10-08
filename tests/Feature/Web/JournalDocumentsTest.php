<?php

namespace Tests\Feature\Web;

use App\Enums\RoleName;
use App\Models\JournalDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Mualliflar uchun fayllar (TZ 4.2.5): admin yuklaydi/almashtiradi, saytda va kabinetda yuklab olinadi.
 */
class JournalDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config(['journal.article_template' => 'downloads/none.docx']);
        $this->manager = User::factory()->withRole(RoleName::ContentManager)->createOne();
    }

    private function docx(string $name = 'Maqola shabloni.docx'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 120, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }

    public function test_admin_uploads_template_and_it_is_linked_everywhere(): void
    {
        $this->get(route('guidelines'))->assertInertia(fn (Assert $page) => $page
            ->where('template', null)
            ->has('documents', 0)
        );

        $this->actingAs(User::factory()->withRole(RoleName::Editor)->createOne())
            ->post(route('admin.settings.documents.store'), [])
            ->assertForbidden();

        // Fayl majburiy, ruxsat etilmagan format rad etiladi
        $this->actingAs($this->manager)
            ->post(route('admin.settings.documents.store'), [
                'kind' => 'template',
                'title' => ['uz' => 'Shablon'],
                'is_active' => true,
                'sort_order' => 0,
            ])
            ->assertSessionHasErrors('file');

        $this->actingAs($this->manager)
            ->post(route('admin.settings.documents.store'), [
                'kind' => 'template',
                'title' => ['uz' => 'Shablon'],
                'is_active' => true,
                'sort_order' => 0,
                'file' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
            ])
            ->assertSessionHasErrors('file');

        $this->actingAs($this->manager)
            ->post(route('admin.settings.documents.store'), [
                'kind' => 'template',
                'title' => ['uz' => 'Maqola shabloni', 'ru' => 'Шаблон статьи'],
                'description' => ['uz' => 'Word formatida'],
                'is_active' => true,
                'sort_order' => 0,
                'file' => $this->docx('../Maqola shabloni.docx'),
            ])
            ->assertSessionHasNoErrors();

        $doc = JournalDocument::query()->firstOrFail();
        Storage::disk('public')->assertExists($doc->path);
        $this->assertSame('docx', $doc->extension);
        $this->assertSame('Maqola shabloni.docx', $doc->original_name);
        $this->assertSame($this->manager->id, $doc->updated_by);

        $url = route('documents.download', $doc->id);

        $this->get(route('guidelines'))->assertInertia(fn (Assert $page) => $page
            ->where('template', $url)
            ->has('documents', 1)
            ->where('documents.0.title', 'Maqola shabloni')
            ->where('documents.0.description', 'Word formatida')
        );

        $author = User::factory()->author()->createOne();
        $this->actingAs($author)->get(route('cabinet.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('links.template', $url));

        // Yuklab olish: asl nom bilan, hisoblagich oshadi
        $this->get($url)
            ->assertOk()
            ->assertDownload('Maqola shabloni.docx');
        $this->assertSame(1, $doc->fresh()->downloads_count);

        $this->actingAs($this->manager)
            ->get(route('admin.settings.index', ['tab' => 'documents']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('documents', 1)
                ->has('documentKinds', 4)
                ->where('documents.0.downloads', 1)
            );
    }

    public function test_file_is_replaced_hidden_and_deleted(): void
    {
        $this->actingAs($this->manager)->post(route('admin.settings.documents.store'), [
            'kind' => 'guide',
            'title' => ['uz' => "Yo'riqnoma"],
            'is_active' => true,
            'sort_order' => 0,
            'file' => UploadedFile::fake()->create('rules.pdf', 50, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $doc = JournalDocument::query()->firstOrFail();
        $old = $doc->path;

        // Faylsiz tahrir — fayl o'zgarmaydi
        $this->actingAs($this->manager)->post(route('admin.settings.documents.update', $doc), [
            '_method' => 'put',
            'kind' => 'guide',
            'title' => ['uz' => "Mualliflar uchun yo'riqnoma"],
            'is_active' => true,
            'sort_order' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertSame($old, $doc->fresh()->path);

        // Yangi fayl — eskisi o'chadi
        $this->actingAs($this->manager)->post(route('admin.settings.documents.update', $doc), [
            '_method' => 'put',
            'kind' => 'guide',
            'title' => ['uz' => "Mualliflar uchun yo'riqnoma"],
            'is_active' => false,
            'sort_order' => 1,
            'file' => UploadedFile::fake()->create('rules-2026.pdf', 60, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $doc->refresh();
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($doc->path);
        $this->assertSame('rules-2026.pdf', $doc->original_name);

        // Nofaol — saytda yo'q, havola 404
        $this->get(route('guidelines'))->assertInertia(fn (Assert $page) => $page->has('documents', 0));
        $this->get(route('documents.download', $doc->id))->assertNotFound();

        $this->actingAs($this->manager)
            ->delete(route('admin.settings.documents.destroy', $doc))
            ->assertSessionHasNoErrors();
        $this->assertModelMissing($doc);
        Storage::disk('public')->assertMissing($doc->path);
    }
}
