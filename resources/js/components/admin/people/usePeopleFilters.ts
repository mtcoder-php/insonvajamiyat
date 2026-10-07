import { router } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';

/**
 * Ro'yxat filtrlari (qidiruv — 350 ms kechikish bilan, qolganlari darhol):
 * faqat ro'yxat va filtrlar qayta yuklanadi. Standart qiymatlar URL'ga yozilmaydi.
 */
export function usePeopleFilters<
    T extends Record<string, string | number | null>,
>(indexUrl: string, initial: T, defaults: T, only: string[]) {
    const form = reactive({ ...initial }) as T;

    const hasFilters = computed(() =>
        (Object.keys(defaults) as (keyof T)[]).some(
            (key) => (form[key] ?? '') !== (defaults[key] ?? ''),
        ),
    );

    function query(page = 1): Record<string, string | number> {
        const result: Record<string, string | number> = {};

        (Object.keys(form) as (keyof T & string)[]).forEach((key) => {
            const value = form[key];

            if (value !== null && value !== '' && value !== defaults[key]) {
                result[key] = value;
            }
        });

        if (page > 1) {
            result.page = page;
        }

        return result;
    }

    function apply(page = 1): void {
        router.get(indexUrl, query(page), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only,
        });
    }

    let timer: ReturnType<typeof setTimeout> | undefined;

    watch(
        () => form.search,
        () => {
            clearTimeout(timer);
            timer = setTimeout(() => apply(), 350);
        },
    );

    watch(
        () =>
            (Object.keys(form) as (keyof T)[])
                .filter((key) => key !== 'search')
                .map((key) => form[key]),
        () => apply(),
    );

    function reset(): void {
        Object.assign(form, defaults);
    }

    return { form, hasFilters, apply, reset };
}
