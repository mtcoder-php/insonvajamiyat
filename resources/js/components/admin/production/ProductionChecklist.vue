<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    CircleCheck,
    CircleDashed,
    ShieldCheck,
    TriangleAlert,
} from '@lucide/vue';
import { ref } from 'vue';
import { cn } from '@/lib/utils';
import type { ProductionArticle } from '@/types';
import { t } from '@/lib/i18n';

/**
 * "Nashr oldidan tekshiruv": avtomatik bandlar + texnik xodimning "formatga mos" belgisi.
 */
const props = defineProps<{ article: ProductionArticle }>();

const saving = ref(false);

function toggleFormat(): void {
    saving.value = true;
    router.put(
        props.article.urls.format,
        { ok: !props.article.production.formatOk },
        { preserveScroll: true, onFinish: () => (saving.value = false) },
    );
}
</script>

<template>
    <section
        class="rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-shadow duration-300 hover:shadow-[0_12px_32px_-18px_rgba(0,36,66,0.25)]"
    >
        <h2
            class="mb-4 flex items-center gap-2 font-sans text-[15px] font-bold text-navy-950"
        >
            <ShieldCheck class="size-[18px] text-brand-600" />
            {{ t('Nashr oldidan tekshiruv') }}
        </h2>
        <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_14rem]">
            <ul class="grid gap-1.5">
                <li
                    v-for="check in article.checks"
                    :key="check.key"
                    class="flex items-start gap-2.5 rounded-lg px-2 py-1.5 transition-colors hover:bg-[#f5f8fc]"
                >
                    <CircleCheck
                        v-if="check.ok"
                        class="mt-px size-[18px] shrink-0 text-emerald-500"
                    />
                    <CircleDashed
                        v-else
                        class="mt-px size-[18px] shrink-0 text-navy-300"
                    />
                    <span class="min-w-0 flex-1">
                        <span
                            :class="
                                cn(
                                    'block text-[13px] font-medium',
                                    check.ok
                                        ? 'text-navy-900'
                                        : 'text-navy-600',
                                )
                            "
                        >
                            {{ check.label }}
                        </span>
                        <span
                            v-if="check.hint"
                            class="block truncate text-[11px] text-navy-400"
                            >{{ check.hint }}</span
                        >
                    </span>
                    <button
                        v-if="check.key === 'format' && article.can.manage"
                        type="button"
                        :disabled="saving"
                        :class="
                            cn(
                                'shrink-0 rounded-md border px-2 py-0.5 text-[11px] font-semibold transition-colors disabled:opacity-50',
                                check.ok
                                    ? 'border-line text-navy-500 hover:border-red-200 hover:text-red-600'
                                    : 'border-brand-200 bg-brand-50 text-brand-700 hover:bg-brand-100',
                            )
                        "
                        @click="toggleFormat"
                    >
                        {{
                            check.ok
                                ? t('Bekor qilish')
                                : t('Mos deb belgilash')
                        }}
                    </button>
                </li>
            </ul>
            <div
                :class="
                    cn(
                        'flex flex-col items-center justify-center gap-2 self-start rounded-xl px-4 py-8 text-center text-[13px] font-medium',
                        article.ready
                            ? 'bg-emerald-50 text-emerald-800'
                            : 'bg-amber-50 text-amber-800',
                    )
                "
            >
                <CircleCheck
                    v-if="article.ready"
                    class="size-8 text-emerald-500"
                />
                <TriangleAlert v-else class="size-8 text-amber-500" />
                {{
                    article.ready
                        ? "Maqola barcha talablar bo'yicha tayyor."
                        : t(':count ta band bajarilmagan.', {
                              count: article.checks.filter((c) => !c.ok).length,
                          })
                }}
            </div>
        </div>
    </section>
</template>
