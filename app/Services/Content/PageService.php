<?php

namespace App\Services\Content;

use App\Enums\AuditEvent;
use App\Enums\Language;
use App\Enums\PageSlug;
use App\Models\Page;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Content\DefaultPages;
use App\Support\Translations;

/**
 * Statik sahifalar: bazadagi matn (tahririyat o'zgartirgan) yoki standart matn (DefaultPages).
 *
 * Bo'lim matnidagi {journal}, {email}, {plagiarism_max} — joriy sozlamalar bilan almashtiriladi,
 * shuning uchun jurnal nomi yoki plagiat chegarasi o'zgarsa matnni qayta tahrirlash shart emas.
 */
class PageService
{
    public const MAX_SECTIONS = 20;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Saytdagi ko'rinish — joriy tilda (bo'lmasa o'zbekcha).
     *
     * @return array{title: string, description: string, sections: list<array{heading: string, body: string}>, updatedAt: string|null}
     */
    public function public(PageSlug $slug): array
    {
        $data = $this->translations($slug);

        $sections = [];

        foreach ($data['sections'] as $section) {
            $heading = $this->pick($section['heading']);
            $body = $this->pick($section['body']);

            if ($heading !== '' || $body !== '') {
                $sections[] = ['heading' => $this->fill($heading), 'body' => $this->fill($body)];
            }
        }

        return [
            'title' => $this->fill($this->pick($data['title'])),
            'description' => $this->fill($this->pick($data['description'])),
            'sections' => $sections,
            'updatedAt' => $data['updatedAt'],
        ];
    }

    /**
     * Admin formasi uchun — barcha tillar.
     *
     * @return array{slug: string, label: string, title: array{uz: string, ru: string, en: string}, description: array{uz: string, ru: string, en: string}, sections: list<array{heading: array{uz: string, ru: string, en: string}, body: array{uz: string, ru: string, en: string}}>, isCustom: bool, updatedAt: string|null, updatedBy: string|null, publicUrl: string, urls: array{update: string, reset: string}}
     */
    public function form(PageSlug $slug): array
    {
        $data = $this->translations($slug);

        return [
            'slug' => $slug->value,
            'label' => $slug->label(),
            'title' => Translations::form($data['title']),
            'description' => Translations::form($data['description']),
            'sections' => array_map(fn (array $s): array => [
                'heading' => Translations::form($s['heading']),
                'body' => Translations::form($s['body']),
            ], $data['sections']),
            'isCustom' => $data['isCustom'],
            'updatedAt' => $data['updatedAt'],
            'updatedBy' => $data['updatedBy'],
            'publicUrl' => route($slug->route()),
            'urls' => [
                'update' => route('admin.settings.pages.update', $slug->value),
                'reset' => route('admin.settings.pages.reset', $slug->value),
            ],
        ];
    }

    /**
     * @param  array{title: array<string, string>, description: array<string, string>, sections: list<array{heading: array<string, string>, body: array<string, string>}>}  $data
     */
    public function save(PageSlug $slug, array $data, User $user): Page
    {
        $page = Page::query()->firstOrNew(['slug' => $slug->value]);
        $isNew = ! $page->exists;

        $page->replaceTranslations('title', $data['title']);
        $page->replaceTranslations('meta_description', $data['description']);
        $page->forceFill([
            'content' => $data['sections'],
            'is_published' => true,
            'updated_by' => $user->id,
        ])->save();

        $this->audit->log(AuditEvent::ContentSaved, null, [
            'type' => 'page',
            'page' => $slug->value,
            'sections' => count($data['sections']),
            'created' => $isNew,
        ], $slug->label(), $user);

        return $page;
    }

    /** Standart matnga qaytarish (bazadagi yozuv o'chiriladi) */
    public function reset(PageSlug $slug, User $user): void
    {
        Page::query()->where('slug', $slug->value)->delete();

        $this->audit->log(AuditEvent::ContentDeleted, null, ['type' => 'page', 'page' => $slug->value, 'reset' => true], $slug->label(), $user);
    }

    /**
     * Formadan kelgan qiymatlar → saqlash uchun (bo'sh bo'limlar tashlab yuboriladi).
     *
     * @param  array<string, mixed>  $input
     * @return array{title: array<string, string>, description: array<string, string>, sections: list<array{heading: array<string, string>, body: array<string, string>}>}
     */
    public static function data(array $input): array
    {
        $sections = [];

        foreach ((array) ($input['sections'] ?? []) as $section) {
            if (! is_array($section)) {
                continue;
            }

            $heading = Translations::clean($section['heading'] ?? []);
            $body = Translations::clean($section['body'] ?? []);

            if ($heading !== [] || $body !== []) {
                $sections[] = ['heading' => $heading, 'body' => $body];
            }
        }

        return [
            'title' => Translations::clean($input['title'] ?? []),
            'description' => Translations::clean($input['description'] ?? []),
            'sections' => array_slice($sections, 0, self::MAX_SECTIONS),
        ];
    }

    /**
     * @return array{title: array<string, string>, description: array<string, string>, sections: list<array{heading: array<string, string>, body: array<string, string>}>, isCustom: bool, updatedAt: string|null, updatedBy: string|null}
     */
    private function translations(PageSlug $slug): array
    {
        $page = Page::query()->with('editor')->where('slug', $slug->value)->first();

        if (! $page instanceof Page) {
            return [...DefaultPages::for($slug), 'isCustom' => false, 'updatedAt' => null, 'updatedBy' => null];
        }

        $sections = [];

        foreach ((array) $page->content as $section) {
            if (is_array($section)) {
                $sections[] = [
                    'heading' => self::strings($section['heading'] ?? []),
                    'body' => self::strings($section['body'] ?? []),
                ];
            }
        }

        /** @var array<string, string> $title */
        $title = $page->getTranslations('title');
        /** @var array<string, string> $description */
        $description = $page->getTranslations('meta_description');

        return [
            'title' => $title,
            'description' => $description,
            'sections' => $sections,
            'isCustom' => true,
            'updatedAt' => $page->updated_at?->toIso8601String(),
            'updatedBy' => $page->editor?->name,
        ];
    }

    /**
     * Joriy til → o'zbekcha → istalgan mavjud til.
     *
     * @param  array<string, string>  $values
     */
    private function pick(array $values): string
    {
        $locale = app()->getLocale();

        foreach ([$locale, Language::default()->value, ...Language::values()] as $lang) {
            $value = trim($values[$lang] ?? '');

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function fill(string $text): string
    {
        $journal = config('journal.name');
        $email = config('journal.contact.email');
        $plagiarism = config('journal.plagiarism_max');

        return strtr($text, [
            '{journal}' => is_string($journal) && $journal !== '' ? $journal : 'Inson va Jamiyat',
            '{email}' => is_string($email) ? $email : '',
            '{plagiarism_max}' => is_numeric($plagiarism) ? rtrim(rtrim(number_format((float) $plagiarism, 1, '.', ''), '0'), '.') : '20',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private static function strings(mixed $value): array
    {
        $result = [];

        foreach ((array) $value as $lang => $text) {
            if (is_string($lang) && is_string($text)) {
                $result[$lang] = $text;
            }
        }

        return $result;
    }
}
