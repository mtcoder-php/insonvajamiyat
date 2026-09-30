<script setup lang="ts">
import { Newspaper } from '@lucide/vue';
import HomeCard from '@/components/web/home/HomeCard.vue';
import { formatDate } from '@/lib/format';
import type { PostItem } from '@/types';

/**
 * "Yangiliklar" (home.png): sana + sarlavha.
 */
defineProps<{ items: PostItem[] }>();
</script>

<template>
    <HomeCard title="Yangiliklar">
        <ul v-if="items.length" class="-mx-2">
            <li v-for="item in items" :key="item.id">
                <div
                    class="group flex items-baseline gap-4 rounded-lg px-2 py-2.5 transition-colors hover:bg-white"
                >
                    <time
                        v-if="item.publishedAt"
                        :datetime="item.publishedAt"
                        class="w-20 shrink-0 text-xs text-navy-400 tabular-nums"
                    >
                        {{ formatDate(item.publishedAt) }}
                    </time>
                    <span
                        class="text-sm leading-snug text-navy-800 transition-colors group-hover:text-brand-700"
                    >
                        {{ item.title }}
                    </span>
                </div>
            </li>
        </ul>
        <div
            v-else
            class="flex flex-col items-center gap-2 py-8 text-center text-sm text-navy-500"
        >
            <Newspaper class="size-7 text-navy-300" />
            Hozircha yangiliklar yo'q.
        </div>
    </HomeCard>
</template>
