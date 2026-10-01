<script setup lang="ts">
import { FileSearch, Search, UserRoundCheck, X } from '@lucide/vue';
import ArticleStatusPill from '@/components/cabinet/ArticleStatusPill.vue';
import { inputClass } from '@/lib/formStyles';
import { formatDate, formatTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import type {
    EditorialListItem,
    EditorialPageProps,
    EditorialQueue,
} from '@/types';

/**
 * Chap ustun: navbatlar, qidiruv va maqolalar ro'yxati (bosilganda o'rtada ochiladi).
 */
defineProps<{
    items: EditorialListItem[];
    meta: EditorialPageProps['articles']['meta'];
    counts: Record<EditorialQueue, number>;
    selected: string | null;
    loading: string | null;
}>();

const queue = defineModel<EditorialQueue>('queue', { required: true });
const search = defineModel<string>('search', { required: true });

const emit = defineEmits<{ open: [uuid: string]; page: [url: string] }>();

const queues: { key: EditorialQueue; label: string }[] = [
    { key: 'new', label: 'Yangi' },
    { key: 'reviewing', label: "Ko'rib chiqilmoqda" },
    { key: 'revision', label: 'Tuzatishda' },
    { key: 'accepted', label: 'Nashrga tayyor' },
    { key: 'mine', label: 'Mening vazifalarim' },
    { key: 'payment', label: "To'lov kutilmoqda" },
    { key: 'published', label: 'Nashr etilgan' },
    { key: 'closed', label: 'Rad / qaytarilgan' },
    { key: 'all', label: 'Barchasi' },
];
</script>

<template>
    <section
        class="flex flex-col overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
    >
        <div class="border-b border-line p-3">
            <div class="flex flex-wrap gap-1">
                <button
                    v-for="item in queues"
                    :key="item.key"
                    type="button"
                    :class="
                        cn(
                            'inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold transition-all',
                            queue === item.key
                                ? 'bg-brand-600 text-white shadow-[0_6px_14px_-8px_rgba(0,108,246,0.9)]'
                                : 'text-navy-600 hover:bg-brand-50 hover:text-brand-700',
                        )
                    "
                    @click="queue = item.key"
                >
                    <UserRoundCheck
                        v-if="item.key === 'mine'"
                        class="size-3.5"
                    />
                    {{ item.label }}
                    <span
                        :class="
                            cn(
                                'rounded-full px-1.5 text-[10px] tabular-nums',
                                queue === item.key
                                    ? 'bg-white/20'
                                    : 'bg-navy-50 text-navy-500',
                            )
                        "
                        >{{ counts[item.key] }}</span
                    >
                </button>
            </div>
            <label class="relative mt-3 block">
                <span class="sr-only">Qidirish</span>
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                />
                <input
                    v-model="search"
                    type="search"
                    placeholder="Maqola nomi yoki muallif..."
                    :class="cn(inputClass, 'h-9 pr-8 pl-9 text-[13px]')"
                />
                <button
                    v-if="search"
                    type="button"
                    class="absolute top-1/2 right-2 -translate-y-1/2 rounded p-0.5 text-navy-400 hover:text-navy-700"
                    aria-label="Qidiruvni tozalash"
                    @click="search = ''"
                >
                    <X class="size-3.5" />
                </button>
            </label>
        </div>

        <ol v-if="items.length" class="divide-y divide-line">
            <li v-for="(item, index) in items" :key="item.uuid">
                <button
                    type="button"
                    :class="
                        cn(
                            'group relative flex w-full gap-3 px-4 py-3 text-left transition-colors',
                            selected === item.uuid
                                ? 'bg-brand-50/70 before:absolute before:inset-y-0 before:left-0 before:w-1 before:rounded-r before:bg-brand-600'
                                : 'hover:bg-[#f8fafd]',
                            loading === item.uuid && 'opacity-60',
                        )
                    "
                    :aria-current="selected === item.uuid ? 'true' : undefined"
                    @click="emit('open', item.uuid)"
                >
                    <span
                        class="w-5 shrink-0 pt-0.5 text-xs font-semibold text-navy-400 tabular-nums"
                    >
                        {{ (meta.from ?? 1) + index }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span
                            :class="
                                cn(
                                    'line-clamp-2 text-[13px] leading-snug font-semibold',
                                    selected === item.uuid
                                        ? 'text-brand-800'
                                        : 'text-navy-900 group-hover:text-brand-700',
                                )
                            "
                        >
                            {{ item.title }}
                        </span>
                        <span
                            class="mt-0.5 block truncate text-xs text-navy-500"
                        >
                            {{ item.author }}
                        </span>
                        <span
                            class="mt-2 flex items-center justify-between gap-2"
                        >
                            <ArticleStatusPill
                                :group="item.statusGroup"
                                :label="item.statusLabel"
                            />
                            <span
                                class="text-right text-[11px] leading-tight text-navy-400 tabular-nums"
                            >
                                {{ formatDate(item.date) }}<br />{{
                                    formatTime(item.date)
                                }}
                            </span>
                        </span>
                    </span>
                </button>
            </li>
        </ol>
        <div
            v-else
            class="flex flex-1 flex-col items-center justify-center gap-2 px-4 py-14 text-center"
        >
            <FileSearch class="size-8 text-navy-300" />
            <p class="text-sm text-navy-500">Bu navbatda maqola yo'q</p>
        </div>

        <nav
            v-if="meta.last_page > 1"
            class="mt-auto flex items-center justify-between gap-2 border-t border-line px-3 py-2.5"
            aria-label="Sahifalar"
        >
            <span class="text-xs text-navy-500 tabular-nums">
                {{ meta.from }}–{{ meta.to }} / {{ meta.total }}
            </span>
            <span class="flex gap-1">
                <template v-for="(link, i) in meta.links.slice(1, -1)" :key="i">
                    <button
                        v-if="link.url"
                        type="button"
                        :class="
                            cn(
                                'flex size-7 items-center justify-center rounded-md text-xs font-semibold tabular-nums transition-colors',
                                link.active
                                    ? 'bg-brand-600 text-white'
                                    : 'text-navy-600 hover:bg-navy-50',
                            )
                        "
                        @click="emit('page', link.url)"
                    >
                        {{ link.label }}
                    </button>
                    <span v-else class="px-1 text-xs text-navy-400">…</span>
                </template>
            </span>
        </nav>
    </section>
</template>
