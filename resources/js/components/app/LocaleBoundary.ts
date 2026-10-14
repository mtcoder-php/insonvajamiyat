import { cloneVNode, defineComponent } from 'vue';
import type { VNode } from 'vue';
import { useLocale } from '@/lib/i18n';

/**
 * Layout ichidagi sahifani til almashganda qayta yaratadi (qo'shimcha DOM o'ramisiz).
 *
 * Sahifa va komponentlarda setup paytida bir marta tarjima qilib qo'yilgan (const meta = …)
 * hisoblanadigan matnlar reaktiv emas. Lug'at yuklanib til haqiqatan almashganda bola
 * vnode'larning kaliti o'zgaradi — Vue ularni qayta mount qiladi va barcha matnlar
 * yangi tilda hisoblanadi. Layout (sidebar, header) esa joyida qoladi.
 */
export default defineComponent({
    name: 'LocaleBoundary',
    setup(_, { slots }) {
        const locale = useLocale();

        return (): VNode[] =>
            (slots.default?.() ?? []).map((vnode, index) =>
                cloneVNode(vnode, {
                    key: `${locale.value}:${String(vnode.key ?? index)}`,
                }),
            );
    },
});
