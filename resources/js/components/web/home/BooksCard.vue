<script setup lang="ts">
import { BookOpen } from '@lucide/vue';
import HomeCard from '@/components/web/home/HomeCard.vue';
import type { RecommendedBook } from '@/types';

/**
 * "Tavsiya etilgan kitoblar" (home.png): muqova, nom, muallif, yil.
 * Muqova: books.cover_image_path (php artisan app:import-book-covers — public/web/books/<nom>.png).
 */
defineProps<{ books: RecommendedBook[] }>();
</script>

<template>
    <HomeCard v-if="books.length" title="Tavsiya etilgan kitoblar">
        <ul class="-mx-2 divide-y divide-[#e8e4db]">
            <li v-for="book in books" :key="book.id">
                <component
                    :is="book.url ? 'a' : 'div'"
                    :href="book.url ?? undefined"
                    :target="book.url ? '_blank' : undefined"
                    :rel="book.url ? 'noopener noreferrer' : undefined"
                    class="group flex gap-3.5 rounded-lg px-2 py-3 transition-colors hover:bg-white"
                >
                    <span
                        class="flex h-20 w-14 shrink-0 items-center justify-center overflow-hidden rounded-sm bg-navy-900 shadow-[0_6px_14px_-6px_rgba(0,30,60,0.55)] ring-1 ring-black/10 transition-all duration-300 group-hover:-translate-y-1 group-hover:-rotate-2 group-hover:shadow-[0_12px_22px_-8px_rgba(0,30,60,0.6)]"
                    >
                        <img
                            v-if="book.coverUrl"
                            :src="book.coverUrl"
                            :alt="book.title"
                            loading="lazy"
                            class="size-full object-cover"
                        />
                        <BookOpen v-else class="size-5 text-gold-300" />
                    </span>
                    <span class="min-w-0 self-center text-xs leading-snug">
                        <span
                            class="block text-sm font-medium text-navy-900 transition-colors group-hover:text-brand-700"
                        >
                            {{ book.title }}
                        </span>
                        <span class="mt-0.5 block text-navy-500">
                            Muallif: {{ book.author }}
                        </span>
                        <span v-if="book.year" class="block text-navy-400">
                            {{ book.year }}
                        </span>
                    </span>
                </component>
            </li>
        </ul>
    </HomeCard>
</template>
