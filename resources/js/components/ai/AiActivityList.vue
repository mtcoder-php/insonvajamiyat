<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import { formatTime, timeAgo } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { AiRequestItem } from '@/types';
import { statusTone, studios } from './aiMeta';
import { t } from '@/lib/i18n';

/** "So'nggi faoliyat" */
defineProps<{ items: AiRequestItem[]; historyUrl: string }>();
</script>

<template>
    <DashCard :title="t('So\'nggi faoliyat')" :href="historyUrl">
        <p v-if="!items.length" class="py-4 text-center text-xs text-navy-400">
            {{ t("Hali so'rov yo'q") }}
        </p>
        <ul v-else class="grid grid-cols-1 gap-1">
            <li v-for="item in items" :key="item.uuid">
                <Link
                    :href="item.url"
                    preserve-scroll
                    class="group flex items-center gap-3 rounded-lg px-2 py-2 transition-colors hover:bg-brand-50/60"
                >
                    <span
                        :class="
                            cn(
                                'flex size-8 shrink-0 items-center justify-center rounded-lg',
                                studios[item.type].soft,
                            )
                        "
                    >
                        <component
                            :is="studios[item.type].icon"
                            class="size-4"
                        />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span
                            class="block truncate text-[12.5px] font-medium text-navy-900 group-hover:text-brand-700"
                        >
                            {{ item.studio }} – {{ item.title }}
                        </span>
                        <span class="block text-[11px] text-navy-400">
                            {{ timeAgo(item.createdAt) }} ·
                            {{ formatTime(item.createdAt)
                            }}<template v-if="item.user">
                                · {{ item.user }}</template
                            >
                        </span>
                    </span>
                    <span
                        v-if="item.status !== 'completed'"
                        :class="
                            cn(
                                'rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1',
                                statusTone[item.status],
                            )
                        "
                        >{{ item.statusLabel }}</span
                    >
                    <span
                        v-else
                        class="text-[11px] font-semibold text-navy-500 tabular-nums"
                    >
                        {{ item.score !== null ? `${item.score}%` : '100%' }}
                    </span>
                </Link>
            </li>
        </ul>
    </DashCard>
</template>
