<script setup lang="ts">
import {
    BellRing,
    CalendarCheck,
    FileClock,
    Megaphone,
    Pin,
} from '@lucide/vue';
import type { Component } from 'vue';
import { formatDate } from '@/lib/format';
import type { PostItem } from '@/types';

/**
 * "Muhim e'lonlar" (home_2.png). E'lonlar sahifasi yangiliklar moduli
 * bilan qo'shiladi — hozircha ro'yxat o'zi.
 */
defineProps<{ items: PostItem[] }>();

const icons: Component[] = [FileClock, CalendarCheck, Megaphone];
</script>

<template>
    <section class="flex flex-col">
        <h2
            class="mb-4 font-serif text-xl font-semibold text-navy-950 sm:text-2xl"
        >
            Muhim e'lonlar
        </h2>

        <div class="flex flex-1 flex-col surface-card p-2">
            <ul v-if="items.length" class="divide-y divide-line">
                <li
                    v-for="(item, index) in items"
                    :key="item.id"
                    class="flex gap-3 p-3"
                >
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-line bg-surface-muted text-brand-700"
                    >
                        <component
                            :is="icons[index % icons.length]"
                            class="size-5"
                            :stroke-width="1.6"
                        />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <h3
                                class="flex items-center gap-1.5 text-sm leading-snug font-semibold text-navy-900"
                            >
                                <Pin
                                    v-if="item.isPinned"
                                    class="size-3.5 shrink-0 text-gold-600"
                                    aria-label="Qadalgan"
                                />
                                {{ item.title }}
                            </h3>
                            <time
                                v-if="item.publishedAt"
                                :datetime="item.publishedAt"
                                class="shrink-0 text-[11px] text-navy-400"
                            >
                                {{ formatDate(item.publishedAt) }}
                            </time>
                        </div>
                        <p
                            v-if="item.excerpt"
                            class="mt-1 line-clamp-2 text-xs leading-relaxed text-navy-500"
                        >
                            {{ item.excerpt }}
                        </p>
                    </div>
                </li>
            </ul>
            <div
                v-else
                class="flex flex-1 flex-col items-center justify-center gap-2 p-8 text-center text-sm text-navy-500"
            >
                <BellRing class="size-7 text-navy-300" />
                Hozircha e'lonlar yo'q.
            </div>
        </div>
    </section>
</template>
