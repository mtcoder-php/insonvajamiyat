<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Award, Globe, Handshake, Sparkles } from '@lucide/vue';
import type { Component } from 'vue';
import { about } from '@/routes';

/**
 * "Nega aynan biz?" — to'q ko'k lenta (home_2.png).
 */
const features: { title: string; text: string; icon: Component }[] = [
    {
        title: 'Tajriba',
        text: 'Yillar davomida shakllangan tahririyat jamoasi',
        icon: Award,
    },
    {
        title: 'Xalqaro indekslar',
        text: 'Xalqaro ilmiy bazalarda indekslanish',
        icon: Globe,
    },
    {
        title: 'Raqamli xizmatlar',
        text: 'AI yordamida tahlil, imlo tekshiruvi va tarjima',
        icon: Sparkles,
    },
    {
        title: 'Ilmiy hamkorlik',
        text: "O'zbekiston va xorijiy ta'lim muassasalari bilan",
        icon: Handshake,
    },
];
</script>

<template>
    <section
        class="relative isolate overflow-hidden bg-navy-gradient text-white"
    >
        <div class="absolute inset-0 -z-10 bg-girih opacity-[0.05]" />
        <div
            class="absolute top-1/2 -left-32 -z-10 size-96 -translate-y-1/2 rounded-full bg-brand-600/20 blur-3xl"
        />

        <div
            class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[22rem_1fr] lg:items-center lg:px-8"
        >
            <div>
                <h2
                    class="font-serif text-3xl font-semibold text-white sm:text-4xl"
                >
                    Nega aynan biz?
                </h2>
                <div class="mt-4 gold-rule w-20" />
                <p class="mt-4 text-sm leading-relaxed text-white/75">
                    "Inson va Jamiyat" jurnali — ilm-fan, dolzarb tadqiqotlar va
                    yuqori akademik standartlarni birlashtirgan nashr.
                </p>
                <Link
                    :href="about()"
                    class="mt-6 inline-flex h-10 items-center gap-2 rounded-full border border-white/40 px-5 text-sm font-semibold transition-colors hover:bg-white/10"
                >
                    Batafsil
                    <ArrowRight class="size-4" />
                </Link>
            </div>

            <ul
                class="grid gap-px overflow-hidden rounded-2xl bg-white/10 sm:grid-cols-2 xl:grid-cols-4"
            >
                <li
                    v-for="feature in features"
                    :key="feature.title"
                    class="flex flex-col items-center bg-navy-950/40 px-5 py-8 text-center"
                >
                    <span
                        class="flex size-14 items-center justify-center rounded-full border border-gold-400/50 bg-white/5 text-gold-300"
                    >
                        <component
                            :is="feature.icon"
                            class="size-6"
                            :stroke-width="1.5"
                        />
                    </span>
                    <h3
                        class="mt-4 font-sans text-base font-semibold text-white"
                    >
                        {{ feature.title }}
                    </h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-white/70">
                        {{ feature.text }}
                    </p>
                </li>
            </ul>
        </div>
    </section>
</template>
