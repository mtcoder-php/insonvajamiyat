<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Laravel paginator havolalari: "1–15 / 128" va sahifa raqamlari.
 */
const props = defineProps<{
    meta: Paginated<unknown>['meta'];
}>();

// Birinchi va oxirgi element — "oldingi"/"keyingi"
const pages = computed(() => props.meta.links.slice(1, -1));
const prev = computed(() => props.meta.links[0]);
const next = computed(() => props.meta.links[props.meta.links.length - 1]);

const itemClass =
    'flex h-9 min-w-9 items-center justify-center rounded-lg px-2 text-sm font-medium transition-all';
</script>

<template>
    <nav
        v-if="meta.total > 0"
        class="flex flex-col items-center justify-between gap-3 sm:flex-row"
        :aria-label="t('Sahifalar')"
    >
        <p class="text-xs text-navy-500 tabular-nums">
            {{
                t(':from–:to / :total ta', {
                    from: meta.from,
                    to: meta.to,
                    total: meta.total,
                })
            }}
        </p>
        <div
            v-if="meta.last_page > 1"
            class="flex flex-wrap items-center gap-1"
        >
            <component
                :is="prev?.url ? Link : 'span'"
                :href="prev?.url ?? undefined"
                preserve-scroll
                preserve-state
                :class="
                    cn(
                        itemClass,
                        prev?.url
                            ? 'text-navy-700 hover:bg-white hover:shadow-sm'
                            : 'text-navy-300',
                    )
                "
                :aria-label="t('Oldingi sahifa')"
            >
                <ChevronLeft class="size-4" />
            </component>
            <template v-for="(page, index) in pages" :key="index">
                <span v-if="!page.url" :class="cn(itemClass, 'text-navy-400')"
                    >…</span
                >
                <Link
                    v-else
                    :href="page.url"
                    preserve-scroll
                    preserve-state
                    :class="
                        cn(
                            itemClass,
                            'tabular-nums',
                            page.active
                                ? 'bg-brand-600 text-white shadow-[0_6px_14px_-8px_rgba(0,108,246,0.9)]'
                                : 'text-navy-700 hover:bg-white hover:shadow-sm',
                        )
                    "
                    :aria-current="page.active ? 'page' : undefined"
                >
                    {{ page.label }}
                </Link>
            </template>
            <component
                :is="next?.url ? Link : 'span'"
                :href="next?.url ?? undefined"
                preserve-scroll
                preserve-state
                :class="
                    cn(
                        itemClass,
                        next?.url
                            ? 'text-navy-700 hover:bg-white hover:shadow-sm'
                            : 'text-navy-300',
                    )
                "
                :aria-label="t('Keyingi sahifa')"
            >
                <ChevronRight class="size-4" />
            </component>
        </div>
    </nav>
</template>
