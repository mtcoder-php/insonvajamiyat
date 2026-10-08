import { computed, ref, shallowRef } from 'vue';
import type { ComputedRef } from 'vue';

/**
 * Interfeys tarjimalari (UZ / RU / EN).
 *
 * Kalit — o'zbekcha matnning o'zi (Laravel `__()` bilan bir xil uslub):
 *   t("Maqolalar katalogi")              → ru: "Каталог статей", en: "Article catalog"
 *   t(':count ta maqola', { count: 5 })  → joy egalari (:name) almashtiriladi
 *   tc(':count ta maqola', 5)            → ko'plik shakllari ("статья|статьи|статей")
 *
 * Lug'atlar — lang/ru.json, lang/en.json (backend `__()` ham shularni ishlatadi).
 * O'zbek tili manba bo'lgani uchun lug'at yuklanmaydi; ru/en alohida chunk bo'lib
 * faqat kerak bo'lganda yuklanadi (app.ts sahifa chizilishidan oldin kutadi).
 */

export type Dictionary = Record<string, string>;
type Replacements = Record<string, string | number | null | undefined>;

export const SOURCE_LOCALE = 'uz';

const loaders = import.meta.glob<Dictionary>('../../../lang/*.json', {
    import: 'default',
});

const cache = new Map<string, Dictionary>();
const currentLocale = ref<string>(SOURCE_LOCALE);
const dictionary = shallowRef<Dictionary>({});
let pending: Promise<void> | null = null;
let pendingLocale: string | null = null;

/** Lug'atni yuklaydi va faol tilni almashtiradi (yuklanmaguncha eski til qoladi) */
export function loadLocale(locale: string | null | undefined): Promise<void> {
    const next = locale || SOURCE_LOCALE;

    if (next === currentLocale.value && pendingLocale === null) {
        return Promise.resolve();
    }

    if (pending && pendingLocale === next) {
        return pending;
    }

    if (next === SOURCE_LOCALE) {
        pendingLocale = null;
        dictionary.value = {};
        currentLocale.value = next;

        return Promise.resolve();
    }

    const cached = cache.get(next);

    if (cached) {
        pendingLocale = null;
        dictionary.value = cached;
        currentLocale.value = next;

        return Promise.resolve();
    }

    const loader = loaders[`../../../lang/${next}.json`];

    pendingLocale = next;
    pending = (loader ? loader() : Promise.resolve({}))
        .catch(() => ({}) as Dictionary)
        .then((loaded) => {
            cache.set(next, loaded);

            // Yuklash davomida boshqa til tanlangan bo'lsa — eskisini qo'llamaymiz
            if (pendingLocale === next) {
                dictionary.value = loaded;
                currentLocale.value = next;
                pendingLocale = null;
            }
        })
        .finally(() => {
            pending = null;
        });

    return pending;
}

function replace(text: string, replacements: Replacements): string {
    // Uzunroq kalitlar avval (":names" ":name" dan oldin) — Laravel bilan bir xil
    return Object.keys(replacements)
        .sort((a, b) => b.length - a.length)
        .reduce(
            (result, key) =>
                result.replaceAll(`:${key}`, String(replacements[key] ?? '')),
            text,
        );
}

/** Tarjima: topilmasa — o'zbekcha kalitning o'zi */
export function t(key: string, replacements: Replacements = {}): string {
    const text = dictionary.value[key] || key;

    return replace(text, replacements);
}

/**
 * Ko'plik shakli: lug'atdagi qiymat "|" bilan ajratiladi.
 *   ru — "one|few|many" (1 статья, 2 статьи, 5 статей)
 *   en — "one|other"    (1 article, 2 articles)
 * :count avtomatik almashtiriladi.
 */
export function tc(
    key: string,
    count: number,
    replacements: Replacements = {},
): string {
    const text = dictionary.value[key] || key;
    const forms = text.split('|');
    let form = forms[0];

    if (forms.length > 1) {
        const category = new Intl.PluralRules(currentLocale.value).select(
            count,
        );
        const order =
            forms.length >= 3 ? ['one', 'few', 'many'] : ['one', 'other'];
        const index = order.indexOf(category);

        form = forms[index === -1 ? forms.length - 1 : index] ?? forms[0];
    }

    return replace(form, { count, ...replacements });
}

/** Joriy til (reaktiv) — sana/son formatlash uchun */
export function locale(): string {
    return currentLocale.value;
}

export function useLocale(): ComputedRef<string> {
    return computed(() => currentLocale.value);
}

/** Intl uchun BCP 47 kodi */
export function intlLocale(): string {
    return (
        { uz: 'uz-Latn-UZ', ru: 'ru-RU', en: 'en-GB' }[currentLocale.value] ??
        currentLocale.value
    );
}

/** Birinchi chizishdan oldin: sahifa JSON'idan tilni o'qiydi (Inertia v3 — script[data-page]) */
export function initialPageLocale(): string {
    try {
        const script = document.querySelector('script[data-page]');
        const element = document.getElementById('app');
        const raw = script?.textContent || element?.dataset.page || '';
        const parsed = JSON.parse(raw) as { props?: { locale?: unknown } };

        return typeof parsed.props?.locale === 'string'
            ? parsed.props.locale
            : SOURCE_LOCALE;
    } catch {
        return SOURCE_LOCALE;
    }
}
