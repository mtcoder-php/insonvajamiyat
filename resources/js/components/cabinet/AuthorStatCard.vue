<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpenCheck,
    CircleCheck,
    FilePenLine,
    FileText,
    Hourglass,
} from '@lucide/vue';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { Component } from 'vue';
import { computed } from 'vue';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import { index as articlesIndex } from '@/routes/cabinet/articles';
import type { AuthorStatCard } from '@/types';

/**
 * Muallif statistikasi kartasi: ikonka, nom, qiymat va izoh ("+2 so'nggi 3 oyda").
 * Bosilganda "Mening maqolalarim" shu holat bo'yicha filtrlangan holda ochiladi.
 */
const props = defineProps<{ card: AuthorStatCard }>();

const meta: Record<
    AuthorStatCard['key'],
    {
        label: string;
        icon: Component;
        tint: string;
        delta: string;
        filter: string | null;
    }
> = {
    total: {
        label: 'Jami maqolalar',
        icon: FileText,
        tint: 'bg-brand-50 text-brand-600 ring-brand-100',
        delta: 'text-emerald-600',
        filter: null,
    },
    reviewing: {
        label: "Ko'rib chiqilayotganlar",
        icon: Hourglass,
        tint: 'bg-amber-50 text-amber-600 ring-amber-100',
        delta: 'text-amber-600',
        filter: 'reviewing',
    },
    revision: {
        label: 'Tuzatish talab qilinganlar',
        icon: FilePenLine,
        tint: 'bg-red-50 text-red-600 ring-red-100',
        delta: 'text-red-600',
        filter: 'revision',
    },
    accepted: {
        label: 'Qabul qilinganlar',
        icon: CircleCheck,
        tint: 'bg-emerald-50 text-emerald-600 ring-emerald-100',
        delta: 'text-emerald-600',
        filter: 'accepted',
    },
    published: {
        label: 'Nashr etilganlar',
        icon: BookOpenCheck,
        tint: 'bg-violet-50 text-violet-600 ring-violet-100',
        delta: 'text-violet-600',
        filter: 'published',
    },
};

const info = computed(() => meta[props.card.key]);

const href = computed<NonNullable<InertiaLinkProps['href']>>(() =>
    articlesIndex(
        info.value.filter
            ? { query: { status: info.value.filter } }
            : undefined,
    ),
);
</script>

<template>
    <Link
        :href="href"
        class="group @container rounded-xl border border-line bg-white p-4 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-1 hover:border-brand-200 hover:shadow-[0_16px_32px_-16px_rgba(0,36,66,0.3)] focus-visible:ring-2 focus-visible:ring-brand-200 focus-visible:outline-none"
    >
        <div class="flex flex-col gap-3 @[12rem]:flex-row @[12rem]:gap-3.5">
            <span
                :class="
                    cn(
                        'flex size-12 shrink-0 items-center justify-center rounded-full ring-4 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-[-6deg]',
                        info.tint,
                    )
                "
            >
                <component :is="info.icon" class="size-5" :stroke-width="1.9" />
            </span>
            <div class="min-w-0">
                <p
                    class="line-clamp-2 text-[13px] leading-4 font-semibold text-navy-900"
                    :title="info.label"
                >
                    {{ info.label }}
                </p>
                <p
                    class="mt-1.5 font-sans text-[28px] leading-none font-bold text-navy-950 tabular-nums"
                >
                    {{ formatNumber(card.value) }}
                </p>
                <p class="mt-2 text-xs text-navy-500">
                    <span
                        v-if="card.delta !== null && card.delta > 0"
                        :class="cn('mr-1 font-semibold', info.delta)"
                    >
                        +{{ card.delta }}
                    </span>
                    {{ card.hint }}
                </p>
            </div>
        </div>
    </Link>
</template>
