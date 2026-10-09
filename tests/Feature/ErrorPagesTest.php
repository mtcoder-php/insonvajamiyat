<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

/**
 * Brendlangan xato sahifalari (App\Support\Http\ErrorPage) va statik 503/500 sahifalari.
 */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]);
    }

    public function test_unknown_url_shows_branded_404_with_locale(): void
    {
        $this->get('/bunday-sahifa-yoq')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('errors/Error')
                ->where('status', 404)
                ->where('locale', 'uz')
                ->has('auth') // web middleware ishlagan — umumiy props bor
            );

        // Tanlangan til fallback sahifasida ham saqlanadi
        $this->post(route('locale.update'), ['locale' => 'ru']);
        $this->get('/yana-yoq-sahifa')->assertInertia(fn (Assert $page) => $page->where('locale', 'ru'));
    }

    public function test_missing_model_and_forbidden_pages_are_branded(): void
    {
        $this->get(route('articles.show', 'mavjud-emas'))
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page->component('errors/Error')->where('status', 404));

        $this->actingAs(User::factory()->withRole(RoleName::Reviewer)->createOne())
            ->get(route('admin.users.index'))
            ->assertForbidden()
            ->assertInertia(fn (Assert $page) => $page->component('errors/Error')->where('status', 403));
    }

    public function test_server_error_is_branded_only_without_debug(): void
    {
        Route::middleware('web')->get('/_test/boom', fn () => throw new RuntimeException('boom'));

        $this->get('/_test/boom')
            ->assertStatus(500)
            ->assertInertia(fn (Assert $page) => $page->component('errors/Error')->where('status', 500));

        config(['app.debug' => true]);
        // Debug rejimida — Laravel'ning tafsilotli sahifasi (dasturchi uchun)
        $this->get('/_test/boom')->assertStatus(500)->assertSee('RuntimeException')->assertDontSee('&quot;component&quot;:&quot;errors\/Error&quot;', false);
    }

    public function test_machines_get_plain_responses(): void
    {
        $this->getJson('/bunday-sahifa-yoq')->assertNotFound()->assertJsonStructure(['message']);

        $this->post('/payments/payme', [], ['Accept' => 'text/html'])->assertOk(); // JSON-RPC javobi o'zgarmaydi
    }

    public function test_expired_csrf_token_redirects_back_with_message(): void
    {
        Route::middleware('web')->post('/_test/form', fn () => abort(419));

        $this->from(route('contact'))
            ->post('/_test/form')
            ->assertRedirect(route('contact'));
    }

    public function test_maintenance_page_is_static_and_branded(): void
    {
        $html = view('errors.503')->render();

        $this->assertStringContainsString('Texnik ishlar olib borilmoqda', $html);
        $this->assertStringContainsString('http-equiv="refresh"', $html);
        $this->assertStringNotContainsString('/build/', $html); // Vite assetlariga bog'liq emas

        $this->assertStringContainsString('Serverda kutilmagan xatolik', view('errors.500')->render());
    }
}
