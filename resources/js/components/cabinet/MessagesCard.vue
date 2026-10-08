<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight, Inbox, UserRoundPen, Building2 } from '@lucide/vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import { formatDate, formatTime } from '@/lib/format';
import type { AuthorMessage } from '@/types';
import { t } from '@/lib/i18n';

/**
 * "So'nggi xabarlar" — tahririyat / taqrizchidan kelgan izohlar.
 */
defineProps<{ items: AuthorMessage[] }>();
</script>

<template>
    <DashCard :title="t('So\'nggi xabarlar')">
        <ul v-if="items.length" class="-mx-2 flex flex-col">
            <li v-for="item in items" :key="item.id">
                <Link
                    :href="item.url"
                    class="group flex items-start gap-3 rounded-lg px-2 py-2.5 transition-colors hover:bg-brand-50/60"
                >
                    <span
                        class="flex size-9 shrink-0 items-center justify-center rounded-full bg-navy-950 text-white transition-transform duration-300 group-hover:scale-105"
                    >
                        <UserRoundPen
                            v-if="item.sender === t('Taqrizchi')"
                            class="size-4"
                        />
                        <Building2 v-else class="size-4" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-baseline justify-between gap-2">
                            <span
                                class="text-[13px] font-semibold text-navy-900"
                            >
                                {{ item.sender }}
                                <span
                                    v-if="item.unread"
                                    class="ml-1 inline-block size-2 rounded-full bg-brand-500 align-middle"
                                    :aria-label="t('O\'qilmagan')"
                                />
                            </span>
                            <span
                                class="shrink-0 text-[11px] text-navy-400 tabular-nums"
                            >
                                {{ formatDate(item.createdAt) }}
                                {{ formatTime(item.createdAt) }}
                            </span>
                        </span>
                        <span class="mt-0.5 line-clamp-2 text-xs text-navy-600">
                            {{ item.message }}
                        </span>
                        <span
                            class="mt-0.5 block truncate text-[11px] text-brand-700/80"
                        >
                            {{ item.title }}
                        </span>
                    </span>
                    <ChevronRight
                        class="mt-2 size-4 shrink-0 text-navy-300 transition-all group-hover:translate-x-0.5 group-hover:text-brand-600"
                    />
                </Link>
            </li>
        </ul>
        <div
            v-else
            class="flex flex-col items-center gap-2 py-8 text-center text-sm text-navy-500"
        >
            <Inbox class="size-6 text-navy-300" />
            {{ t("Hozircha xabarlar yo'q") }}
        </div>
    </DashCard>
</template>
