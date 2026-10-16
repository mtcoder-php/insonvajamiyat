<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, FileText, Star } from '@lucide/vue';
import { t } from '@/lib/i18n';
import type { LibraryBook } from '@/types';

/**
 * Kutubxona kartochkasi: vertikal muqova (hoverda kitob kabi og'adi), nom, muallif,
 * yil, yo'nalish va PDF belgisi. Bosilganda kitob sahifasi ochiladi.
 */
defineProps<{ book: LibraryBook }>();

const languages: Record<string, string> = { uz: "O'zb", ru: 'Рус', en: 'Eng' };
</script>

<template>
    <Link
        :href="book.pageUrl"
        class="group flex flex-col rounded-2xl border border-[#ebe8e1] bg-white p-4 transition-all duration-300 hover:-translate-y-1 hover:border-brand-200 hover:shadow-[0_22px_40px_-22px_rgba(0,30,60,0.45)]"
    >
        <div
            class="relative flex aspect-[3/4] shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#f3efe6] to-[#e9e3d6] [perspective:900px]"
        >
            <div
                class="relative aspect-[2/3] w-[66%] origin-left overflow-hidden rounded-[3px_8px_8px_3px] bg-navy-900 shadow-[0_14px_26px_-12px_rgba(0,30,60,0.7)] transition-transform duration-500 group-hover:[transform:rotateY(-14deg)_translateX(4px)] group-hover:shadow-[12px_20px_34px_-14px_rgba(0,30,60,0.7)]"
            >
                <img
                    v-if="book.coverUrl"
                    :src="book.coverUrl"
                    :alt="book.title"
                    loading="lazy"
                    class="size-full object-cover"
                />
                <div
                    v-else
                    class="flex size-full flex-col items-center justify-center gap-3 bg-girih-gold p-4 text-center"
                >
                    <BookOpen class="size-8 text-gold-300" />
                    <span
                        class="line-clamp-4 font-serif text-sm leading-snug font-bold text-white"
                        >{{ book.title }}</span
                    >
                </div>
                <!-- Kitob qirrasi -->
                <span
                    class="absolute inset-y-0 left-0 w-2 bg-gradient-to-r from-black/30 to-transparent"
                    aria-hidden="true"
                />
            </div>
            <span
                v-if="book.isFeatured"
                class="absolute top-2.5 left-2.5 inline-flex items-center gap-1 rounded-full bg-gold-500 px-2 py-0.5 text-[11px] font-semibold text-white shadow-sm"
            >
                <Star class="size-3 fill-current" /> {{ t('Tavsiya') }}
            </span>
            <span
                v-if="book.hasFile"
                class="absolute top-2.5 right-2.5 inline-flex items-center gap-1 rounded-full bg-white/95 px-2 py-0.5 text-[11px] font-semibold text-red-700 shadow-sm"
            >
                <FileText class="size-3" /> PDF
            </span>
        </div>

        <div class="flex flex-1 flex-col pt-4">
            <span
                v-if="book.subject"
                class="mb-2 line-clamp-1 text-[11px] font-semibold tracking-wide text-brand-700 uppercase"
                >{{ book.subject.name }}</span
            >
            <h3
                class="line-clamp-2 font-serif text-[17px] leading-snug font-bold text-navy-950 transition-colors group-hover:text-brand-700"
            >
                {{ book.title }}
            </h3>
            <p class="mt-1.5 line-clamp-1 text-sm text-navy-600">
                {{ book.author }}
            </p>
            <p
                class="mt-auto flex items-center gap-2 pt-3 text-xs text-navy-400"
            >
                <span v-if="book.year">{{ book.year }}</span>
                <span v-if="book.year && book.pages">·</span>
                <span v-if="book.pages">{{
                    t(':count bet', { count: book.pages })
                }}</span>
                <span
                    class="ml-auto rounded bg-navy-50 px-1.5 py-0.5 font-semibold text-navy-600"
                    >{{ languages[book.language] }}</span
                >
            </p>
        </div>
    </Link>
</template>
