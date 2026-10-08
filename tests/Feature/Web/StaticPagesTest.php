<?php

namespace Tests\Feature\Web;

use App\Enums\EditorialBoardRole;
use App\Enums\RoleName;
use App\Models\EditorialBoardMember;
use App\Models\Page;
use App\Models\User;
use App\Notifications\Web\ContactMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * "Jurnal haqida", "Mualliflar uchun yo'riqnoma", "Aloqa": standart matn, admin tahriri,
 * tahririyat kengashi va aloqa formasi.
 */
class StaticPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->manager = User::factory()->withRole(RoleName::ContentManager)->createOne();
    }

    public function test_pages_render_default_content_with_tokens_filled(): void
    {
        config(['journal.plagiarism_max' => 15]);

        $this->get(route('about'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('web/About')
            ->where('page.sections', fn ($sections) => count($sections) >= 3)
            ->where('page.sections', fn ($sections) => collect($sections)->contains(
                fn (array $s): bool => str_contains($s['body'], '15%') && ! str_contains($s['body'], '{plagiarism_max}'),
            ))
            ->has('board', 0)
            ->has('facts')
            ->has('stats')
        );

        $this->get(route('guidelines'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('web/Guidelines')
            ->where('plagiarismMax', 15)
            ->where('submitUrl', route('register'))
            ->where('page.title', fn (string $title) => $title !== '')
        );

        $this->actingAs($this->manager)->get(route('guidelines'))
            ->assertInertia(fn (Assert $page) => $page->where('submitUrl', route('cabinet.articles.create')));

        $this->get(route('contact'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('web/Contact')
            ->has('page.sections')
        );
    }

    public function test_admin_edits_page_per_locale_and_resets_it(): void
    {
        $this->actingAs(User::factory()->withRole(RoleName::Editor)->createOne())
            ->put(route('admin.settings.pages.update', 'about'), ['title' => ['uz' => 'X']])
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->put(route('admin.settings.pages.update', 'about'), [
                'title' => ['uz' => ''],
                'sections' => [],
            ])
            ->assertSessionHasErrors('title.uz');

        $this->actingAs($this->manager)
            ->put(route('admin.settings.pages.update', 'about'), [
                'title' => ['uz' => 'Jurnal tarixi', 'ru' => 'История журнала', 'en' => ''],
                'description' => ['uz' => 'Qisqa tavsif'],
                'sections' => [
                    ['heading' => ['uz' => 'Tashkil etilishi', 'ru' => 'Основание'], 'body' => ['uz' => "Jurnal {journal} nomi bilan.\n\n- birinchi\n- ikkinchi"]],
                    ['heading' => ['uz' => ''], 'body' => ['uz' => '']],
                ],
            ])
            ->assertSessionHasNoErrors();

        $page = Page::query()->where('slug', 'about')->firstOrFail();
        $this->assertSame($this->manager->id, $page->updated_by);
        $this->assertCount(1, $page->content);

        $this->get(route('about'))->assertInertia(fn (Assert $p) => $p
            ->where('page.title', 'Jurnal tarixi')
            ->has('page.sections', 1)
            ->where('page.sections.0.heading', 'Tashkil etilishi')
            ->where('page.sections.0.body', fn (string $body) => ! str_contains($body, '{journal}'))
        );

        // Rus tilida — tarjimasi bor joyi ruscha, yo'q joyi o'zbekcha
        $this->post(route('locale.update'), ['locale' => 'ru']);
        $this->get(route('about'))->assertInertia(fn (Assert $p) => $p
            ->where('page.title', 'История журнала')
            ->where('page.sections.0.heading', 'Основание')
        );
        $this->post(route('locale.update'), ['locale' => 'uz']);

        $this->actingAs($this->manager)
            ->get(route('admin.settings.index', ['tab' => 'pages']))
            ->assertInertia(fn (Assert $p) => $p
                ->has('pages', 3)
                ->where('pages.0.slug', 'about')
                ->where('pages.0.isCustom', true)
                ->where('pages.1.isCustom', false)
            );

        $this->actingAs($this->manager)
            ->delete(route('admin.settings.pages.reset', 'about'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('pages', ['slug' => 'about']);
        $this->get(route('about'))->assertInertia(fn (Assert $p) => $p
            ->where('page.title', fn (string $title) => $title !== 'Jurnal tarixi')
        );

        $this->actingAs($this->manager)
            ->put(route('admin.settings.pages.update', 'unknown'), ['title' => ['uz' => 'X']])
            ->assertNotFound();
    }

    public function test_editorial_board_is_managed_and_grouped_by_role(): void
    {
        $this->actingAs($this->manager)
            ->post(route('admin.settings.board.store'), [
                'role' => 'member',
                'full_name' => ['uz' => 'Saidova Nilufar'],
                'orcid' => 'abc',
                'country' => 'UZB',
            ])
            ->assertSessionHasErrors(['orcid', 'country']);

        $this->actingAs($this->manager)
            ->post(route('admin.settings.board.store'), [
                'role' => 'member',
                'full_name' => ['uz' => 'Saidova Nilufar'],
                'organization' => ['uz' => 'ToshDU'],
                'country' => 'uz',
                'orcid' => '0000-0002-1825-009x',
                'is_active' => true,
                'sort_order' => 1,
                'photo' => UploadedFile::fake()->image('photo.jpg', 400, 500),
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->manager)
            ->post(route('admin.settings.board.store'), [
                'role' => 'chief_editor',
                'full_name' => ['uz' => 'Karimov Akmal'],
                'is_active' => true,
                'sort_order' => 0,
            ])
            ->assertSessionHasNoErrors();

        $member = EditorialBoardMember::query()->where('role', 'member')->firstOrFail();
        $this->assertSame('UZ', $member->country);
        $this->assertSame('0000-0002-1825-009X', $member->orcid);
        $this->assertNotNull($member->photo_path);
        Storage::disk('public')->assertExists($member->photo_path);

        // Bosh muharrir birinchi guruh
        $this->get(route('about'))->assertInertia(fn (Assert $p) => $p
            ->has('board', 2)
            ->where('board.0.role', EditorialBoardRole::ChiefEditor->value)
            ->where('board.0.members.0.name', 'Karimov Akmal')
            ->where('board.1.members.0.photoUrl', fn ($url) => is_string($url))
        );

        // Nofaol — saytda ko'rinmaydi; rasm olib tashlanadi
        $photo = $member->photo_path;
        $this->actingAs($this->manager)
            ->post(route('admin.settings.board.update', $member), [
                '_method' => 'put',
                'role' => 'member',
                'full_name' => ['uz' => 'Saidova Nilufar'],
                'is_active' => false,
                'sort_order' => 1,
                'remove_photo' => true,
            ])
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($photo);
        $this->get(route('about'))->assertInertia(fn (Assert $p) => $p->has('board', 1));

        $this->actingAs($this->manager)
            ->delete(route('admin.settings.board.destroy', $member))
            ->assertSessionHasNoErrors();
        $this->assertModelMissing($member);
    }

    public function test_contact_form_sends_message_to_journal_email(): void
    {
        Notification::fake();
        config(['journal.contact.email' => 'editor@example.uz']);

        $this->post(route('contact.send'), [
            'name' => 'Ali',
            'email' => 'ali@example.com',
            'message' => 'qisqa',
        ])->assertSessionHasErrors('message');

        // Bot (yashirin maydon to'ldirilgan) — qabul qilinmaydi
        $this->post(route('contact.send'), [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'message' => 'Buy cheap things right now please',
            'website' => 'http://spam.example',
        ])->assertSessionHasErrors('website');

        $this->post(route('contact.send'), [
            'name' => 'Ali Valiyev',
            'email' => 'ALI@example.com',
            'subject' => 'Maqola holati',
            'message' => 'Maqolam qachon ko\'rib chiqiladi?',
        ])->assertSessionHasNoErrors();

        Notification::assertSentOnDemand(
            ContactMessageNotification::class,
            fn (ContactMessageNotification $n, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'editor@example.uz'
                && $n->email === 'ali@example.com'
                && $n->subject === 'Maqola holati',
        );
        Notification::assertSentOnDemandTimes(ContactMessageNotification::class, 1);
    }

    public function test_contact_form_is_throttled(): void
    {
        Notification::fake();

        $send = fn () => $this->post(route('contact.send'), [
            'name' => 'Ali',
            'email' => 'ali@example.com',
            'message' => 'Assalomu alaykum, savolim bor.',
        ]);

        $send()->assertSessionHasNoErrors();
        $send()->assertSessionHasNoErrors();
        $send()->assertSessionHasNoErrors();
        $send()->assertSessionHasErrors('message');

        Notification::assertSentOnDemandTimes(ContactMessageNotification::class, 3);
    }
}
