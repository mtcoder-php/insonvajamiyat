import { computed } from 'vue';
import type { ComputedRef } from 'vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { cabinetMainNavigation } from '@/navigation/cabinet';
import type { CabinetNavItem } from '@/navigation/cabinet';

const pathOf = (item: CabinetNavItem): string =>
    new URL(toUrl(item.href), 'http://localhost').pathname;

/**
 * Kabinet menyusi va faol band: joriy URL'ga eng uzun mos keluvchi havola
 * (/cabinet/articles/create da "Mening maqolalarim" emas, "Yangi maqola" faol).
 */
export function useCabinetNav(): {
    items: CabinetNavItem[];
    isActive: (item: CabinetNavItem) => boolean;
    activeHref: ComputedRef<string | null>;
} {
    const { currentUrl } = useCurrentUrl();
    const items = cabinetMainNavigation();

    const activeHref = computed(() => {
        const path = currentUrl.value;
        let best: string | null = null;

        for (const item of items) {
            if (item.disabled) {
                continue;
            }

            const href = pathOf(item);
            const matches = item.exact
                ? path === href
                : path === href || path.startsWith(`${href}/`);

            if (matches && (best === null || href.length > best.length)) {
                best = href;
            }
        }

        return best;
    });

    const isActive = (item: CabinetNavItem): boolean =>
        !item.disabled && pathOf(item) === activeHref.value;

    return { items, isActive, activeHref };
}
