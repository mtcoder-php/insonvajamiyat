<script setup lang="ts">
import { Newspaper } from '@lucide/vue';
import { formatDate } from '@/lib/format';
import type { PostItem } from '@/types';

/**
 * "Yangiliklar" paneli (home.png): sana + sarlavha ro'yxati.
 */
defineProps<{ items: PostItem[] }>();
</script>

<template>
    <section class="flex flex-col">
        <h2
            class="mb-4 flex items-center gap-2 font-serif text-lg font-semibold text-navy-950 sm:text-xl"
        >
            <Newspaper class="size-5 text-brand-600" />
            Yangiliklar
        </h2>

        <div class="flex flex-1 flex-col surface-card px-5 py-2">
            <ul v-if="items.length" class="divide-y divide-line">
                <li
                    v-for="item in items"
                    :key="item.id"
                    class="flex items-baseline gap-3 py-3 text-sm"
                >
                    <time
                        v-if="item.publishedAt"
                        :datetime="item.publishedAt"
                        class="w-20 shrink-0 rounded bg-surface-muted px-1.5 py-0.5 text-center text-[11px] text-navy-500"
                    >
                        {{ formatDate(item.publishedAt) }}
                    </time>
                    <span class="leading-snug text-navy-800">
                        {{ item.title }}
                    </span>
                </li>
            </ul>
            <div
                v-else
                class="flex flex-1 flex-col items-center justify-center gap-2 py-8 text-sm text-navy-500"
            >
                <Newspaper class="size-7 text-navy-300" />
                Hozircha yangiliklar yo'q.
            </div>
        </div>
    </section>
</template>
