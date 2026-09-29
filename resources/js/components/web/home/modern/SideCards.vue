<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Quote, UserRoundPen } from '@lucide/vue';
import { guidelines } from '@/routes';

/**
 * O'ng ustun: shior kartasi va "Mualliflar uchun" chaqiruvi (home_2.png).
 */
</script>

<template>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-1 lg:pt-12">
        <figure
            class="relative flex min-h-44 flex-col justify-end overflow-hidden rounded-xl bg-navy-gradient p-6 text-white shadow-card"
        >
            <div class="absolute inset-0 bg-girih opacity-[0.07]" />
            <Quote
                class="absolute top-4 right-4 size-16 text-white/10"
                :stroke-width="1"
            />
            <blockquote
                class="relative font-serif text-xl leading-snug font-medium italic"
            >
                Ilm — insonning eng buyuk boyligidir.
            </blockquote>
            <div class="relative mt-4 gold-rule w-16" />
        </figure>

        <div
            class="flex flex-col rounded-xl border border-brand-200 bg-brand-50 p-5"
        >
            <div class="flex gap-3">
                <span
                    class="flex size-11 shrink-0 items-center justify-center rounded-full bg-white text-brand-700 shadow-card"
                >
                    <UserRoundPen class="size-5" />
                </span>
                <div>
                    <h3 class="font-serif text-lg font-semibold text-navy-950">
                        Mualliflar uchun
                    </h3>
                    <p class="mt-1 text-sm leading-relaxed text-navy-600">
                        Maqola yuborish, yo'riqnoma va shablonlarni yuklab
                        oling.
                    </p>
                </div>
            </div>
            <Link
                :href="guidelines()"
                class="mt-4 inline-flex h-9 w-fit items-center gap-1.5 rounded-full border border-brand-300 bg-white px-4 text-sm font-semibold text-brand-700 transition-colors hover:bg-brand-100"
            >
                Batafsil
                <ArrowRight class="size-4" />
            </Link>
        </div>
    </div>
</template>
