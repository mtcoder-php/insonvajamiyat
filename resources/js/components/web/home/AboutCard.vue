<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { computed } from 'vue';
import HomeCard from '@/components/web/home/HomeCard.vue';
import { about } from '@/routes';

/**
 * "Jurnal haqida" — to'q ko'k karta, o'ngda naqsh (home.png).
 */
const journal = computed(() => usePage().props.journal);

// O'ng tomondagi rozetka naqshi: 8 nurli yulduz va doiralar
const petals = Array.from({ length: 8 }, (_, i) => i * 45);
</script>

<template>
    <HomeCard
        tone="navy"
        title="Jurnal haqida"
        class="group bg-gradient-to-br from-navy-900 via-navy-900 to-navy-700"
    >
        <svg
            class="pointer-events-none absolute top-1/2 -right-16 size-56 -translate-y-1/2 text-white/10 transition-transform duration-[1200ms] ease-out group-hover:rotate-45"
            viewBox="0 0 200 200"
            fill="none"
            stroke="currentColor"
            stroke-width="1.2"
            aria-hidden="true"
        >
            <circle cx="100" cy="100" r="92" />
            <circle cx="100" cy="100" r="70" />
            <circle cx="100" cy="100" r="18" />
            <g
                v-for="angle in petals"
                :key="angle"
                :transform="`rotate(${angle} 100 100)`"
            >
                <path d="M100 12 L114 60 L100 82 L86 60 Z" />
                <path d="M100 30 C120 50 120 70 100 82 C80 70 80 50 100 30 Z" />
            </g>
            <rect
                x="58"
                y="58"
                width="84"
                height="84"
                transform="rotate(45 100 100)"
            />
            <rect x="58" y="58" width="84" height="84" />
        </svg>

        <p class="relative max-w-[16rem] text-sm leading-relaxed text-white/85">
            «{{ journal.name }}» ilmiy jurnali tarix, etnologiya, etnografiya,
            antropologiya va filologiya sohalaridagi yangi ilmiy natijalarni
            e'lon qilish, fanlararo tadqiqotlarni rivojlantirish va xalqaro
            ilmiy hamkorlikni mustahkamlashga qaratilgan.
        </p>
        <Link
            :href="about()"
            class="group/btn relative mt-5 inline-flex h-9 items-center gap-1.5 rounded-full border border-white/50 px-5 text-sm font-medium text-white transition-all hover:bg-white hover:text-navy-900"
        >
            Batafsil
            <ArrowRight
                class="size-4 transition-transform group-hover/btn:translate-x-0.5"
            />
        </Link>
    </HomeCard>
</template>
