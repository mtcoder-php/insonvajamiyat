<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Eye } from '@lucide/vue';
import SimplePager from '@/components/admin/ui/SimplePager.vue';
import { formatDateTime, formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { AiRequestItem, AiRequestTypeValue, SimpleMeta } from '@/types';
import { statusTone, studios } from './aiMeta';
import { t, tk } from '@/lib/i18n';

/**
 * "Mening so'rovlarim" / Tarix jadvali. Filtrlar (tur, hammasi/mening) — `filter` hodisasi orqali.
 */
defineProps<{
    title: string;
    items: AiRequestItem[];
    meta: SimpleMeta;
    type: AiRequestTypeValue | null;
    showTypes?: boolean;
    scope?: 'own' | 'all';
    canScope?: boolean;
    selected?: string | null;
}>();

const emit = defineEmits<{
    filter: [
        query: {
            type?: AiRequestTypeValue | null;
            scope?: 'own' | 'all';
            page?: number;
        },
    ];
}>();

const typeTabs: { value: AiRequestTypeValue | null; label: string }[] = [
    { value: null, label: tk('Barchasi') },
    { value: 'spell_check', label: 'Proofreader' },
    { value: 'translation', label: 'Translator' },
    { value: 'analysis', label: 'Analytics' },
];
</script>

<template>
    <section
        class="rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
    >
        <header
            class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3"
        >
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="font-sans text-[15px] font-bold text-navy-950">
                    {{ title }}
                </h2>
                <nav v-if="showTypes" class="flex flex-wrap gap-1">
                    <button
                        v-for="tab in typeTabs"
                        :key="tab.label"
                        type="button"
                        :class="
                            cn(
                                'rounded-md px-2.5 py-1 text-xs font-semibold transition-colors',
                                type === tab.value
                                    ? 'bg-brand-50 text-brand-700 underline decoration-2 underline-offset-[6px]'
                                    : 'text-navy-500 hover:bg-navy-50 hover:text-navy-800',
                            )
                        "
                        @click="emit('filter', { type: tab.value, page: 1 })"
                    >
                        {{ t(tab.label) }}
                    </button>
                </nav>
            </div>
            <div
                v-if="canScope"
                class="inline-flex rounded-lg bg-[#eef3fa] p-1 text-xs font-semibold"
            >
                <button
                    v-for="option in [
                        { value: 'own', label: t('Mening') },
                        { value: 'all', label: t('Hammasi') },
                    ] as const"
                    :key="option.value"
                    type="button"
                    :class="
                        cn(
                            'rounded-md px-3 py-1 transition-all',
                            scope === option.value
                                ? 'bg-white text-brand-700 shadow-sm'
                                : 'text-navy-500 hover:text-navy-800',
                        )
                    "
                    @click="emit('filter', { scope: option.value, page: 1 })"
                >
                    {{ option.label }}
                </button>
            </div>
        </header>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[42rem] text-left text-[13px]">
                <thead
                    class="bg-[#fafcff] text-[11px] font-semibold tracking-wide text-navy-500 uppercase"
                >
                    <tr>
                        <th class="px-3 py-2.5">{{ t('Tur') }}</th>
                        <th class="px-3 py-2.5">{{ t('Matn') }}</th>
                        <th class="px-3 py-2.5">{{ t('Til') }}</th>
                        <th class="px-3 py-2.5">{{ t('Holat') }}</th>
                        <th class="px-3 py-2.5 text-right">
                            {{ t('Tokenlar') }}
                        </th>
                        <th class="px-3 py-2.5">{{ t('Vaqt') }}</th>
                        <th class="px-3 py-2.5 text-right">
                            {{ t('Amallar') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr
                        v-for="item in items"
                        :key="item.uuid"
                        :class="
                            cn(
                                'transition-colors hover:bg-brand-50/40',
                                selected === item.uuid && 'bg-brand-50/60',
                            )
                        "
                    >
                        <td class="px-3 py-2.5">
                            <span
                                class="inline-flex items-center gap-2 font-medium text-navy-800"
                            >
                                <span
                                    :class="
                                        cn(
                                            'flex size-6 items-center justify-center rounded-md',
                                            studios[item.type].soft,
                                        )
                                    "
                                >
                                    <component
                                        :is="studios[item.type].icon"
                                        class="size-3.5"
                                    />
                                </span>
                                {{ item.studio.replace('AI ', '') }}
                            </span>
                        </td>
                        <td class="px-3 py-2.5">
                            <span
                                class="block max-w-[15rem] truncate text-navy-800"
                                :title="item.title"
                                >{{ item.title }}</span
                            >
                            <span
                                v-if="item.user"
                                class="block text-[11px] text-navy-400"
                                >{{ item.user }}</span
                            >
                            <span
                                v-else-if="item.article"
                                class="block max-w-[15rem] truncate text-[11px] text-brand-700/80"
                                :title="item.article.title"
                                >{{
                                    t('Maqola: :title', {
                                        title: item.article.title,
                                    })
                                }}</span
                            >
                        </td>
                        <td class="px-3 py-2.5 whitespace-nowrap text-navy-600">
                            {{ item.languages }}
                        </td>
                        <td class="px-3 py-2.5">
                            <span
                                :class="
                                    cn(
                                        'inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1',
                                        statusTone[item.status],
                                    )
                                "
                            >
                                {{ item.statusLabel }}
                            </span>
                        </td>
                        <td
                            class="px-3 py-2.5 text-right text-navy-600 tabular-nums"
                        >
                            {{ formatNumber(item.tokens) }}
                        </td>
                        <td class="px-3 py-2.5 whitespace-nowrap text-navy-500">
                            {{ formatDateTime(item.createdAt) }}
                        </td>
                        <td class="px-3 py-2.5 text-right">
                            <Link
                                :href="item.url"
                                preserve-scroll
                                class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-brand-700 transition-colors hover:bg-brand-50"
                            >
                                <Eye class="size-3.5" /> {{ t("Ko'rish") }}
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!items.length">
                        <td
                            colspan="7"
                            class="px-4 py-8 text-center text-sm text-navy-400"
                        >
                            {{ t("So'rovlar yo'q") }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <footer v-if="meta.lastPage > 1" class="border-t border-line px-4 py-3">
            <SimplePager
                :meta="meta"
                @go="(page) => emit('filter', { page })"
            />
        </footer>
    </section>
</template>
