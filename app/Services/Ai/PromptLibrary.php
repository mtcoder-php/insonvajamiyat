<?php

namespace App\Services\Ai;

use App\Enums\AiRequestType;
use App\Models\PromptTemplate;

/**
 * AI ko'rsatmalari (system prompt) — admin paneldan tahrirlanadi (prompt_templates).
 * Bazada faol shablon bo'lmasa, shu yerdagi standart matn ishlatiladi.
 *
 * O'rinbosarlar: {source_language}, {target_language}, {checks}, {text}
 */
class PromptLibrary
{
    public const LANGUAGES = ['uz', 'ru', 'en'];

    /** @var array<string, array{name: string, type: AiRequestType, system: string, user: string, temperature: float, max_tokens: int}> */
    private const DEFAULTS = [
        'proofreader' => [
            'name' => 'AI Proofreader — imlo va uslub tekshiruvi',
            'type' => AiRequestType::SpellCheck,
            'temperature' => 0.1,
            'max_tokens' => 4096,
            'system' => <<<'TXT'
Siz ilmiy jurnalning tajribali muharrir-korrektorisiz. Sizga {source_language} tilidagi ilmiy matn bo'lagi beriladi.

Tekshiriladigan jihatlar: {checks}.

Qoidalar:
- Matnning mazmuni, faktlari, raqamlari, iqtiboslari va muallif fikrini o'zgartirmang.
- O'zbek tilidagi matnda lotin alifbosidagi amaldagi imlo qoidalariga amal qiling (o', g', tutuq belgisi ').
- Har bir xato uchun "original" ga matndagi aynan o'sha bo'lakni yozing (1–8 so'z, matnda qanday bo'lsa xuddi shunday, tinish belgilari bilan), "suggestion" ga esa uning to'g'ri variantini.
- "type": spelling | grammar | punctuation | style | terminology.
- "reason": qisqa izoh o'zbek tilida (10 so'zgacha).
- "score": matn sifati, 0–100 (100 — xatosiz, ilmiy uslubga to'liq mos).
- Xato bo'lmasa "issues" bo'sh massiv bo'lsin. Taxminiy yoki ta'mga bog'liq o'zgarishlarni taklif qilmang.

Javobni FAQAT quyidagi JSON ko'rinishida qaytaring, boshqa hech qanday matnsiz:
{"issues":[{"original":"","suggestion":"","type":"","reason":""}],"score":0,"summary":""}
TXT,
            'user' => "Tekshiriladigan matn:\n<<<\n{text}\n>>>",
        ],
        'translator' => [
            'name' => 'AI Translator — ilmiy tarjima',
            'type' => AiRequestType::Translation,
            'temperature' => 0.3,
            'max_tokens' => 8192,
            'system' => <<<'TXT'
Siz ilmiy matnlar bo'yicha professional tarjimonsiz. Matnni {source_language} tilidan {target_language} tiliga tarjima qiling.

Qoidalar:
- So'zma-so'z emas, ilmiy uslubda tarjima qiling: akademik ohang, terminologiya va mazmun izchilligini saqlang.
- Soha terminlarini {target_language} tilidagi qabul qilingan ilmiy atamalar bilan bering.
- Raqamlar, formulalar, sanalar, iqtiboslar (masalan, Karimov, 2020), havolalar, DOI va bibliografik yozuvlarni o'zgartirmang.
- Xatboshi (paragraf) tuzilishini saqlang.
- Faqat tarjima matnini qaytaring — izoh, sarlavha yoki qo'shimcha so'zlarsiz.
TXT,
            'user' => '{text}',
        ],
        'analytics' => [
            'name' => 'AI Analytics — ilmiy uslub tahlili',
            'type' => AiRequestType::Analysis,
            'temperature' => 0.2,
            'max_tokens' => 2048,
            'system' => <<<'TXT'
Siz ilmiy jurnalning tajribali taqrizchisisiz. {source_language} tilidagi ilmiy matnni ilmiy uslub va sifat jihatidan baholang.

Baholash mezonlari (har biri 0–100):
- academic_style — ilmiy uslubga mosligi;
- clarity — fikr aniqligi va tushunarliligi;
- structure — tuzilishi va mantiqiy ketma-ketligi;
- terminology — terminlarning to'g'ri va izchil ishlatilishi;
- coherence — gaplar va xatboshilar orasidagi bog'liqlik.

Izohlarni o'zbek tilida, aniq va qisqa yozing. Matnda bo'lmagan narsani da'vo qilmang.

Javobni FAQAT quyidagi JSON ko'rinishida qaytaring, boshqa hech qanday matnsiz:
{"score":0,"metrics":{"academic_style":0,"clarity":0,"structure":0,"terminology":0,"coherence":0},"strengths":[""],"weaknesses":[""],"recommendations":[""],"summary":""}
TXT,
            'user' => "Baholanadigan matn:\n<<<\n{text}\n>>>",
        ],
    ];

    /** Proofreader tekshiruv turlari (forma belgilari) */
    public const CHECKS = [
        'spelling' => 'imlo va grammatik xatolar',
        'style' => 'uslub va ifoda (ilmiy uslub)',
        'terminology' => 'terminlar va iqtiboslar',
    ];

    public static function keyFor(AiRequestType $type): string
    {
        return match ($type) {
            AiRequestType::SpellCheck => 'proofreader',
            AiRequestType::Translation => 'translator',
            AiRequestType::Analysis => 'analytics',
        };
    }

    /** Faol shablon (bazadan) yoki standart (saqlanmagan) shablon */
    public function forType(AiRequestType $type): PromptTemplate
    {
        $key = self::keyFor($type);

        $template = PromptTemplate::query()->where('key', $key)->where('is_active', true)->first();

        return $template ?? $this->make($key);
    }

    /** Admin sahifasi uchun: standart shablonlar bazada bo'lishini ta'minlaydi */
    public function ensureDefaults(): void
    {
        foreach (array_keys(self::DEFAULTS) as $key) {
            if (! PromptTemplate::query()->where('key', $key)->exists()) {
                $this->make($key)->save();
            }
        }
    }

    /** Standart matnga qaytarish */
    public function reset(string $key): PromptTemplate
    {
        $template = PromptTemplate::query()->where('key', $key)->firstOrFail();
        $default = $this->make($key);

        $template->forceFill([
            'system_prompt' => $default->system_prompt,
            'user_prompt_template' => $default->user_prompt_template,
            'temperature' => $default->temperature,
            'max_tokens' => $default->max_tokens,
            'is_active' => true,
        ])->save();

        return $template;
    }

    public static function exists(string $key): bool
    {
        return isset(self::DEFAULTS[$key]);
    }

    /**
     * @param  array<string, string>  $vars
     */
    public static function render(string $template, array $vars): string
    {
        $pairs = [];

        foreach ($vars as $name => $value) {
            $pairs['{'.$name.'}'] = $value;
        }

        return strtr($template, $pairs);
    }

    public static function languageName(?string $code): string
    {
        return match ($code) {
            'ru' => 'rus',
            'en' => 'ingliz',
            default => "o'zbek",
        };
    }

    /** Interfeys uchun: O'zbekcha, Ruscha, Inglizcha */
    public static function languageLabel(?string $code): string
    {
        return match ($code) {
            'ru' => 'Ruscha',
            'en' => 'Inglizcha',
            default => "O'zbekcha",
        };
    }

    private function make(string $key): PromptTemplate
    {
        $default = self::DEFAULTS[$key];

        return new PromptTemplate([
            'key' => $key,
            'name' => $default['name'],
            'type' => $default['type'],
            'source_language' => 'uz',
            'target_language' => null,
            'system_prompt' => $default['system'],
            'user_prompt_template' => $default['user'],
            'model' => null,
            'temperature' => $default['temperature'],
            'max_tokens' => $default['max_tokens'],
            'is_active' => true,
        ]);
    }
}
