<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen } from '@lucide/vue';
import HomeCard from '@/components/web/home/HomeCard.vue';
import { index as libraryIndex } from '@/routes/library';
import type { LibraryBook } from '@/types';
import { t } from '@/lib/i18n';

/**
 * "Tavsiya etilgan kitoblar" (home.png): kutubxonadagi tavsiya etilgan kitoblar —
 * muqova, nom, muallif, yil; bosilganda kitob sahifasi, "Barchasi" — kutubxona.
 */
defineProps<{ books: LibraryBook[] }>();
</script>

<template>
    <HomeCard
        v-if="books.length"
        :title="t('Tavsiya etilgan kitoblar')"
        :href="libraryIndex()"
        :link-text="t('Kutubxona')"
    >
        <ul class="-mx-2 divide-y divide-[#e8e4db]">
            <li v-for="book in books" :key="book.id">
                <Link
                    :href="book.pageUrl"
                    class="group flex gap-4 rounded-lg px-2 py-3.5 transition-colors hover:bg-white"
                >
                    <span
                        class="flex h-28 w-20 shrink-0 items-center justify-center overflow-hidden rounded-sm bg-navy-900 shadow-[0_6px_14px_-6px_rgba(0,30,60,0.55)] ring-1 ring-black/10 transition-all duration-300 group-hover:-translate-y-1 group-hover:-rotate-2 group-hover:shadow-[0_12px_22px_-8px_rgba(0,30,60,0.6)]"
                    >
                        <img
                            v-if="book.coverUrl"
                            :src="book.coverUrl"
                            :alt="book.title"
                            loading="lazy"
                            class="size-full object-cover"
                        />
                        <BookOpen v-else class="size-7 text-gold-300" />
                    </span>
                    <span class="min-w-0 self-center text-xs leading-snug">
                        <span
                            class="block text-[15px] leading-snug font-medium text-navy-900 transition-colors group-hover:text-brand-700"
                        >
                            {{ book.title }}
                        </span>
                        <span class="mt-1.5 block text-[13px] text-navy-500">
                            {{ t('Muallif: :name', { name: book.author }) }}
                        </span>
                        <span
                            v-if="book.year"
                            class="mt-0.5 block text-[13px] text-navy-400"
                        >
                            {{ book.year }}
                        </span>
                    </span>
                </Link>
            </li>
        </ul>
    </HomeCard>
</template>
