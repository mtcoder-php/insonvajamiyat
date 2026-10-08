<?php

namespace Tests\Feature\Web;

use App\Enums\ArticleStatus;
use App\Enums\RoleName;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Interfeys tarjimalari (UZ / RU / EN): lang/ru.json, lang/en.json.
 *
 * Kalit — o'zbekcha matn. Frontend'da t('...') / tc('...') / tk('...') bilan, backend'da __('...') bilan
 * ishlatilgan har bir kalit ikkala lug'atda bo'lishi va joy egalari (:name) saqlanishi shart.
 */
class InterfaceTranslationTest extends TestCase
{
    use RefreshDatabase;

    /** Backend: __() matnlari shu papkalarda qidiriladi (barchasi tarjima qilingan bo'lishi shart) */
    private const PHP_SOURCES = [
        'app',
        'resources/views',
    ];

    /**
     * @return array<string, string>
     */
    private function dictionary(string $locale): array
    {
        $decoded = json_decode((string) file_get_contents(lang_path("{$locale}.json")), true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);

        /** @var array<string, string> $decoded */
        return $decoded;
    }

    /**
     * @return array<string, string> kalit => qaysi fayldan
     */
    private function frontendKeys(): array
    {
        $keys = [];
        $call = '/\bt[ck]?\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/s';
        $layout = '/defineOptions\(\{\s*layout:\s*\{(.*?)\}\s*,?\s*\}\)/s';
        $layoutField = '/(?:title|description):\s*([\'"])((?:\\\\.|(?!\1).)*)\1/s';

        foreach (File::allFiles(resource_path('js')) as $file) {
            $path = str_replace('\\', '/', $file->getPathname());

            if (! in_array($file->getExtension(), ['vue', 'ts'], true)
                || preg_match('#/js/(actions|routes|wayfinder)/#', $path)
                || str_ends_with($path, 'lib/i18n.ts')) {
                continue;
            }

            $source = $file->getContents();
            preg_match_all($call, $source, $matches);

            foreach ($matches[2] as $i => $key) {
                $keys[$this->unescape($matches[1][$i], $key)] = $file->getRelativePathname();
            }

            // Auth sahifalari: sarlavha layout'da tarjima qilinadi
            if (str_contains($path, '/pages/auth/') && preg_match($layout, $source, $block)) {
                preg_match_all($layoutField, $block[1], $fields);

                foreach ($fields[2] as $i => $key) {
                    $keys[$this->unescape($fields[1][$i], $key)] = $file->getRelativePathname();
                }
            }
        }

        return $keys;
    }

    /**
     * @return array<string, string>
     */
    private function backendKeys(): array
    {
        $keys = [];

        foreach (self::PHP_SOURCES as $source) {
            $path = base_path($source);
            $files = File::allFiles($path);

            foreach ($files as $file) {
                preg_match_all('/__\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/s', (string) file_get_contents($file->getPathname()), $matches);

                foreach ($matches[2] as $i => $key) {
                    // Guruh kalitlari (validation.required, passwords.sent) — lang/*/*.php da
                    if (preg_match('/^[a-z_]+\.[a-z_.]+$/', $key)) {
                        continue;
                    }

                    $keys[$this->unescape($matches[1][$i], $key)] = $source;
                }
            }
        }

        return $keys;
    }

    /** Manba koddagi satr literalini haqiqiy matnga aylantiradi ("\n", \' va h.k.) */
    private function unescape(string $quote, string $literal): string
    {
        return $quote === '"'
            ? stripcslashes($literal)
            : str_replace(["\\'", '\\\\'], ["'", '\\'], $literal);
    }

    /**
     * @return list<string>
     */
    private function placeholders(string $text): array
    {
        preg_match_all('/:([a-zA-Z_]+)/', $text, $matches);
        $names = array_values(array_unique($matches[1]));
        sort($names);

        return $names;
    }

    public function test_dictionaries_are_valid_and_have_the_same_keys(): void
    {
        $ru = $this->dictionary('ru');
        $en = $this->dictionary('en');

        $this->assertNotEmpty($ru);
        $this->assertSame([], array_values(array_diff(array_keys($ru), array_keys($en))), 'en.json da yetishmaydi');
        $this->assertSame([], array_values(array_diff(array_keys($en), array_keys($ru))), 'ru.json da yetishmaydi');

        foreach (['ru' => $ru, 'en' => $en] as $locale => $dictionary) {
            foreach ($dictionary as $key => $value) {
                $this->assertNotSame('', trim($value), "{$locale}: bo'sh tarjima — {$key}");

                // Ko'plik shakllari ("|") ham, oddiy matn ham kalitdagi joy egalarini saqlaydi
                foreach (explode('|', $value) as $form) {
                    $this->assertSame(
                        $this->placeholders($key),
                        $this->placeholders($form),
                        "{$locale}: joy egalari mos emas — {$key}",
                    );
                }
            }
        }
    }

    public function test_every_frontend_key_is_translated(): void
    {
        $ru = $this->dictionary('ru');
        $en = $this->dictionary('en');
        $missing = [];

        foreach ($this->frontendKeys() as $key => $file) {
            if (! isset($ru[$key], $en[$key])) {
                $missing[] = "{$file}: {$key}";
            }
        }

        $this->assertSame([], $missing, "Tarjimasi yo'q kalitlar (lang/ru.json, lang/en.json)");
    }

    public function test_every_backend_message_is_translated(): void
    {
        $ru = $this->dictionary('ru');
        $en = $this->dictionary('en');
        $missing = [];

        foreach ($this->backendKeys() as $key => $source) {
            if (! isset($ru[$key], $en[$key])) {
                $missing[] = "{$source}: {$key}";
            }
        }

        $this->assertSame([], $missing);
    }

    public function test_selected_locale_translates_shared_data_and_validation(): void
    {
        config(['journal.frequency' => 'Yiliga 4 marta (kvartal)']);

        $this->post(route('locale.update'), ['locale' => 'ru']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('lang="ru"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'ru')
                ->where('journal.frequency', '4 раза в год (ежеквартально)')
                ->where('heroSlides.0.buttonText', 'Смотреть статьи')
            );

        $this->post(route('newsletter.subscribe'), ['email' => 'not-an-email'])
            ->assertSessionHasErrors(['email' => 'Неверный адрес электронной почты.']);

        // Laravel validatsiya xabarlari ham (lang/ru/validation.php)
        app()->setLocale('ru');
        $this->assertSame('Поле «Имя» обязательно для заполнения.', __('validation.required', ['attribute' => 'Имя']));

        $this->post(route('locale.update'), ['locale' => 'en']);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('journal.frequency', '4 times a year (quarterly)')
                ->where('heroSlides.0.buttonText', 'Browse articles')
            );
    }

    public function test_uzbek_is_the_source_language(): void
    {
        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'uz')
                ->where('journal.frequency', config('journal.frequency'))
            );

        app()->setLocale('uz');
        $this->assertSame('Ism to\'ldirilishi shart.', __('validation.required', ['attribute' => 'ism']));
    }

    public function test_author_notifications_use_the_author_language(): void
    {
        $editor = User::factory()->withRole(RoleName::Editor)->createOne(['locale' => 'uz']);
        $author = User::factory()->author()->createOne(['locale' => 'ru']);
        $article = Article::factory()->status(ArticleStatus::UnderReview)->createOne([
            'submitter_id' => $author->id,
            'handling_editor_id' => $editor->id,
            'submitted_at' => now()->subDay(),
        ]);

        // Muharrir o'zbek tilida ishlaydi — muallifga bildirishnoma rus tilida boradi
        $this->actingAs($editor)
            ->post(route('admin.articles.messages.store', $article->uuid), ['body' => 'Fayl qabul qilindi.'])
            ->assertSessionHasNoErrors();

        $notification = $author->notifications()->firstOrFail();
        $this->assertSame('Новое сообщение от редакции', $notification->data['title']);
        $this->assertSame('uz', app()->getLocale());
        $this->assertSame('ru', $author->preferredLocale());
    }
}
