<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Interfeys tarjimalari (UZ / RU / EN): lang/ru.json, lang/en.json.
 *
 * Kalit — o'zbekcha matn. Frontend'da t('...') / tc('...') bilan, backend'da __('...') bilan
 * ishlatilgan har bir kalit ikkala lug'atda bo'lishi va joy egalari (:name) saqlanishi shart.
 */
class InterfaceTranslationTest extends TestCase
{
    use RefreshDatabase;

    /** Backend: ommaviy sayt va kirish sahifalariga chiqadigan __() matnlari */
    private const PHP_SOURCES = [
        'app/Http/Controllers/Web',
        'app/Http/Requests/Web',
        'app/Actions/Fortify/CreateNewUser.php',
        'app/Http/Middleware/EnsureAccountIsActive.php',
        'app/Http/Middleware/EnsureUserIsStaff.php',
        'app/Providers/FortifyServiceProvider.php',
        'app/Support/Seo/SeoMeta.php',
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
        $call = '/\btc?\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/s';
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

            foreach ($matches[2] as $key) {
                $keys[stripslashes($key)] = $file->getRelativePathname();
            }

            // Auth sahifalari: sarlavha layout'da tarjima qilinadi
            if (str_contains($path, '/pages/auth/') && preg_match($layout, $source, $block)) {
                preg_match_all($layoutField, $block[1], $fields);

                foreach ($fields[2] as $key) {
                    $keys[stripslashes($key)] = $file->getRelativePathname();
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
            $files = is_dir($path) ? File::allFiles($path) : [new \SplFileInfo($path)];

            foreach ($files as $file) {
                preg_match_all('/__\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/s', (string) file_get_contents($file->getPathname()), $matches);

                foreach ($matches[2] as $key) {
                    $keys[stripslashes($key)] = $source;
                }
            }
        }

        return $keys;
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

    public function test_public_backend_messages_are_translated(): void
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
}
