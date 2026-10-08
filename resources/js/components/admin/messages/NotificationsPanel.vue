<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Bell, BellOff, CheckCheck, Megaphone } from '@lucide/vue';
import SimplePager from '@/components/admin/ui/SimplePager.vue';
import { formatDate, formatTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { NotificationItem, SimpleMeta } from '@/types';
import { t } from '@/lib/i18n';

/** Xodimning bildirishnomalari: barchasi / o'qilmaganlar, hammasini o'qilgan deb belgilash */
defineProps<{
    page: { data: NotificationItem[]; meta: SimpleMeta };
    onlyUnread: boolean;
    unread: number;
    readAllUrl: string;
}>();

const emit = defineEmits<{ filter: [unread: boolean]; page: [page: number] }>();

function markAll(url: string): void {
    router.post(url, {}, { preserveScroll: true });
}
</script>

<template>
    <section
        class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
    >
        <header
            class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-3"
        >
            <div class="flex gap-1">
                <button
                    v-for="opt in [
                        { unread: false, label: t('Barchasi') },
                        { unread: true, label: t('O\'qilmaganlar') },
                    ]"
                    :key="opt.label"
                    type="button"
                    :class="
                        cn(
                            'rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors',
                            onlyUnread === opt.unread
                                ? 'bg-navy-900 text-white'
                                : 'text-navy-600 hover:bg-brand-50 hover:text-brand-700',
                        )
                    "
                    @click="emit('filter', opt.unread)"
                >
                    {{ opt.label }}
                </button>
            </div>
            <button
                v-if="unread > 0"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-700 transition-colors hover:bg-brand-50"
                @click="markAll(readAllUrl)"
            >
                <CheckCheck class="size-4" />
                {{ t("Hammasini o'qilgan deb belgilash") }}
            </button>
        </header>

        <ul v-if="page.data.length" class="divide-y divide-line">
            <li v-for="n in page.data" :key="n.id">
                <button
                    type="button"
                    :class="
                        cn(
                            'group flex w-full items-start gap-3 px-5 py-3.5 text-left transition-colors hover:bg-brand-50/40',
                            !n.read && 'bg-brand-50/30',
                        )
                    "
                    @click="router.post(n.openUrl)"
                >
                    <span
                        :class="
                            cn(
                                'mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full transition-transform group-hover:scale-110',
                                n.read
                                    ? 'bg-navy-50 text-navy-400'
                                    : 'bg-brand-600 text-white',
                            )
                        "
                    >
                        <Megaphone
                            v-if="n.kind === 'broadcast'"
                            class="size-4"
                        />
                        <Bell v-else class="size-4" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-start justify-between gap-3">
                            <span
                                :class="
                                    cn(
                                        'text-[13px] text-navy-900',
                                        !n.read && 'font-bold',
                                    )
                                "
                                >{{ n.title }}</span
                            >
                            <span
                                class="shrink-0 text-[11px] text-navy-400 tabular-nums"
                            >
                                {{ formatDate(n.createdAt) }}
                                {{ formatTime(n.createdAt) }}
                            </span>
                        </span>
                        <span
                            v-if="n.articleTitle"
                            class="mt-0.5 block truncate text-xs text-brand-700"
                        >
                            {{ n.articleTitle }}
                        </span>
                        <span
                            v-if="n.body"
                            class="mt-0.5 line-clamp-3 text-xs whitespace-pre-line text-navy-500"
                        >
                            {{ n.body }}
                        </span>
                    </span>
                </button>
            </li>
        </ul>
        <div
            v-else
            class="flex flex-col items-center gap-2 px-6 py-14 text-center text-sm text-navy-500"
        >
            <BellOff class="size-8 text-navy-300" />
            {{
                onlyUnread
                    ? "O'qilmagan bildirishnomalar yo'q"
                    : "Hozircha bildirishnomalar yo'q"
            }}
        </div>

        <div class="border-t border-line px-5 py-3">
            <SimplePager :meta="page.meta" @go="(p) => emit('page', p)" />
        </div>
    </section>
</template>
