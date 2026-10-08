<?php

namespace Tests\Feature\Web;

use App\Models\Article;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Frontend / SEO auditi (72-bosqich): meta description va tadbir vaqti.
 */
class FrontendAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_meta_description_is_shared_for_every_public_page(): void
    {
        config(['journal.description' => 'Tarix va falsafa jurnali']);

        // Inertia gidratatsiyada server tegini o'chiradi — layout uni shu prop'dan qayta qo'yadi
        foreach (['home', 'news.index', 'events.index', 'login'] as $route) {
            $this->get(route($route))->assertInertia(fn (Assert $page) => $page
                ->where('seoDescription', 'Tarix va falsafa jurnali')
            );
        }

        // Statik sahifalar — tahririyat yozgan qisqa tavsif
        $this->get(route('about'))->assertInertia(fn (Assert $page) => $page
            ->where('seoDescription', fn (string $d) => str_contains($d, 'tahririyat kengashi'))
        );

        // Katalog va arxiv — o'z tavsifi (server HTML'ida ham, prop'da ham bir xil)
        $this->get(route('articles.index'))
            ->assertSee('jurnalida nashr etilgan ilmiy maqolalar', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('seoDescription', fn (string $d) => str_contains($d, 'jurnalida nashr etilgan ilmiy maqolalar'))
            );

        $this->get(route('issues.index'))->assertInertia(fn (Assert $page) => $page
            ->where('seoDescription', fn (string $d) => str_contains($d, 'ilmiy jurnalining barcha sonlari'))
        );

        // Maqola sahifasi — annotatsiya
        $article = Article::factory()->published()->createOne([
            'abstract' => ['uz' => 'Ushbu maqolada Buxoro hunarmandchiligi tarixi tahlil qilinadi.'],
        ]);
        $this->get(route('articles.show', $article->slug))->assertInertia(fn (Assert $page) => $page
            ->where('seoDescription', 'Ushbu maqolada Buxoro hunarmandchiligi tarixi tahlil qilinadi.')
        );

        // Rus tilida — tarjima
        $this->post(route('locale.update'), ['locale' => 'ru']);
        $this->get(route('articles.index'))->assertInertia(fn (Assert $page) => $page
            ->where('seoDescription', fn (string $d) => str_contains($d, 'научные статьи') || str_contains($d, 'статьи'))
        );
    }

    public function test_event_time_is_sent_as_wall_clock_without_timezone(): void
    {
        $event = Event::factory()->createOne([
            'starts_at' => now()->addDays(5)->setTime(21, 30),
            'ends_at' => now()->addDays(5)->setTime(23, 0),
        ]);

        // "…T21:30:00" — brauzer uni mahalliy vaqt deb o'qiydi (UTC deb +5 soat siljitmaydi)
        $this->get(route('events.show', $event->slug))->assertInertia(fn (Assert $page) => $page
            ->where('event.startsAt', $event->starts_at->format('Y-m-d').'T21:30:00')
            ->where('event.endsAt', $event->starts_at->format('Y-m-d').'T23:00:00')
        );
    }
}
