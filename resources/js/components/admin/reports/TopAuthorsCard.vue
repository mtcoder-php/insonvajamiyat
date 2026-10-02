<script setup lang="ts">
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import { cn } from '@/lib/utils';
import type { ReportTopAuthor } from '@/types';

/**
 * "Eng faol mualliflar" — davrda yuborgan maqolalari soni bo'yicha.
 */
defineProps<{ items: ReportTopAuthor[] }>();

const medal = [
    'bg-amber-400 text-white',
    'bg-slate-400 text-white',
    'bg-orange-400 text-white',
];

function initials(name: string): string {
    return name
        .split(/\s+/)
        .map((part) => part.charAt(0))
        .join('')
        .slice(0, 2)
        .toUpperCase();
}
</script>

<template>
    <DashCard title="Eng faol mualliflar">
        <p
            v-if="items.length === 0"
            class="py-6 text-center text-sm text-navy-400"
        >
            Tanlangan davrda maqola yuborilmagan
        </p>
        <ol v-else class="space-y-1">
            <li
                v-for="(author, i) in items"
                :key="author.name"
                class="group -mx-2 flex items-center gap-3 rounded-lg px-2 py-1.5 transition-colors hover:bg-surface-muted"
            >
                <span
                    :class="
                        cn(
                            'flex size-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold tabular-nums',
                            medal[i] ?? 'bg-navy-50 text-navy-600',
                        )
                    "
                >
                    {{ i + 1 }}
                </span>
                <span
                    class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-[11px] font-bold text-brand-700 ring-2 ring-white transition-transform group-hover:scale-110"
                >
                    {{ initials(author.name) }}
                </span>
                <span class="min-w-0 flex-1">
                    <span
                        class="block truncate text-[13px] font-semibold text-navy-900"
                    >
                        {{ author.name }}
                    </span>
                    <span
                        v-if="author.organization"
                        class="block truncate text-[11px] text-navy-400"
                        :title="author.organization"
                    >
                        {{ author.organization }}
                    </span>
                </span>
                <span
                    class="text-xs whitespace-nowrap text-navy-500 tabular-nums"
                >
                    <b class="text-navy-900">{{ author.articles }}</b> maqola
                </span>
            </li>
        </ol>
    </DashCard>
</template>
