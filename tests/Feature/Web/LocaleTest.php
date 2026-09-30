<?php

namespace Tests\Feature\Web;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Sayt tilini almashtirish (header'dagi UZ / RU / EN).
 */
class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_locale_and_available_locales_are_shared()
    {
        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', config('app.locale'))
                ->has('locales', 3)
                ->where('locales.0.code', 'uz')
                ->where('locales.1.code', 'ru')
                ->where('locales.2.code', 'en')
            );
    }

    public function test_guest_can_switch_locale()
    {
        $this->from(route('home'))
            ->post(route('locale.update'), ['locale' => 'ru'])
            ->assertRedirect(route('home'))
            ->assertSessionHas('locale', 'ru');

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'ru'));
    }

    public function test_translatable_content_follows_selected_locale()
    {
        Subject::factory()->create(['name' => ['uz' => 'Tarix', 'ru' => 'История', 'en' => 'History']]);

        $this->post(route('locale.update'), ['locale' => 'ru']);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('subjects.0.name', 'История'));
    }

    public function test_unknown_locale_is_rejected()
    {
        $this->post(route('locale.update'), ['locale' => 'de'])
            ->assertSessionHasErrors('locale');

        $this->assertNull(session('locale'));
    }

    public function test_authenticated_user_locale_is_saved_to_profile()
    {
        $user = User::factory()->author()->create(['locale' => 'uz']);

        $this->actingAs($user)
            ->post(route('locale.update'), ['locale' => 'en'])
            ->assertSessionHasNoErrors();

        $this->assertSame('en', $user->fresh()?->locale);
    }
}
