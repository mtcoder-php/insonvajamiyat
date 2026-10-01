<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight, Newspaper } from '@lucide/vue';
import HomeCard from '@/components/web/home/HomeCard.vue';
import { formatDate } from '@/lib/format';
import { index as newsIndex } from '@/routes/news';
import type { PostItem } from '@/types';

/**
 * "Yangiliklar" (home.png): sana + sarlavha, har biri yangilik sahifasiga havola.
 */
defineProps<{ items: PostItem[] }>();
</script>

<template>
    <HomeCard
        title="Yangiliklar"
        :href="newsIndex()"
        link-text="Barcha yangiliklar"
    >
        <ul v-if="items.length" class="-mx-2 divide-y divide-[#ece8df]">
            <li v-for="item in items" :key="item.id">
                <Link
                    :href="item.url"
                    class="group flex items-baseline gap-4 rounded-lg px-2 py-3 transition-colors hover:bg-white"
                >
                    <time
                        v-if="item.publishedAt"
                        :datetime="item.publishedAt"
                        class="w-20 shrink-0 text-xs text-navy-400 tabular-nums"
                    >
                        {{ formatDate(item.publishedAt) }}
                    </time>
                    <span
                        class="min-w-0 flex-1 text-sm leading-snug text-navy-800 transition-colors group-hover:text-brand-700"
                    >
                        {{ item.title }}
                    </span>
                    <ChevronRight
                        class="size-4 shrink-0 self-center text-navy-300 opacity-0 transition-all group-hover:translate-x-0.5 group-hover:text-brand-600 group-hover:opacity-100"
                    />
                </Link>
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
