import { router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { ref } from 'vue';
import type { SettingsFilters, SettingsTab } from '@/types';

/**
 * Sahifalangan tablar (yangiliklar, tadbirlar) uchun filtr / qidiruv / sahifa:
 * faqat shu tab ma'lumoti qayta yuklanadi (partial reload).
 */
export function useSettingsQuery(
    indexUrl: string,
    tab: Extract<SettingsTab, 'posts' | 'events'>,
    filters: () => SettingsFilters,
) {
    const search = ref(filters().q);

    function apply(patch: Partial<SettingsFilters> = {}, page = 1): void {
        const next = { ...filters(), q: search.value.trim(), ...patch };
        const query: Record<string, string | number> = { tab };

        (['type', 'when', 'q'] as const).forEach((key) => {
            if (next[key]) {
                query[key] = next[key];
            }
        });

        if (page > 1) {
            query.page = page;
        }

        router.get(indexUrl, query, {
            preserveState: true,
            preserveScroll: true,
            only: [tab, 'filters'],
        });
    }

    watchDebounced(
        search,
        (value) => {
            if (value.trim() !== filters().q) {
                apply();
            }
        },
        { debounce: 400 },
    );

    return { search, apply };
}
