<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import SkylineIllustration from '@/components/web/SkylineIllustration.vue';
import { index as articlesIndex } from '@/routes/articles';
import type { BannerItem } from '@/types';

/**
 * Klassik hero slayderi (home.png). Slaydlar — admin paneldagi bannerlar;
 * banner bo'lmasa jurnal shiori bilan standart slayd chiqadi.
 */
const props = defineProps<{ banners: BannerItem[] }>();

type Slide = {
    key: string;
    title: string;
    subtitle: string | null;
    imageUrl: string | null;
    href: string;
    external: boolean;
    buttonText: string;
};

const defaultHref = articlesIndex.url();

const slides = computed<Slide[]>(() => {
    if (props.banners.length === 0) {
        return [
            {
                key: 'default',
                title: 'Insonni anglash — jamiyatni anglashdir.',
                subtitle: 'Understanding Humanity, Understanding Society.',
                imageUrl: null,
                href: defaultHref,
                external: false,
                buttonText: "Maqolalarni ko'rish",
            },
        ];
    }

    return props.banners.map((banner) => ({
        key: `banner-${banner.id}`,
        title: banner.title,
        subtitle: banner.subtitle,
        imageUrl: banner.imageUrl,
        href: banner.linkUrl ?? defaultHref,
        external: /^https?:\/\//.test(banner.linkUrl ?? ''),
        buttonText: banner.buttonText ?? "Maqolalarni ko'rish",
    }));
});

const active = ref(0);
const paused = ref(false);
let timer: ReturnType<typeof setInterval> | undefined;

function go(index: number): void {
    const count = slides.value.length;
    active.value = (index + count) % count;
}

onMounted(() => {
    const reduceMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;

    if (slides.value.length > 1 && !reduceMotion) {
        timer = setInterval(() => {
            if (!paused.value) {
                go(active.value + 1);
            }
        }, 7000);
    }
});

onBeforeUnmount(() => clearInterval(timer));
</script>

<template>
    <section
        class="relative isolate h-[22rem] overflow-hidden bg-navy-950 text-white sm:h-[26rem]"
        aria-roledescription="carousel"
        aria-label="Asosiy slayder"
        @mouseenter="paused = true"
        @mouseleave="paused = false"
        @focusin="paused = true"
        @focusout="paused = false"
    >
        <div
            v-for="(slide, index) in slides"
            :key="slide.key"
            :class="[
                'absolute inset-0 transition-opacity duration-700',
                index === active
                    ? 'opacity-100'
                    : 'pointer-events-none opacity-0',
            ]"
            role="group"
            aria-roledescription="slide"
            :aria-label="`${index + 1} / ${slides.length}`"
            :aria-hidden="index !== active"
        >
            <img
                v-if="slide.imageUrl"
                :src="slide.imageUrl"
                alt=""
                class="absolute inset-0 -z-10 size-full object-cover"
                :loading="index === 0 ? 'eager' : 'lazy'"
            />
            <template v-else>
                <div class="absolute inset-0 -z-10 bg-navy-gradient" />
                <div class="absolute inset-0 -z-10 bg-girih opacity-[0.05]" />
                <div
                    class="absolute top-0 left-1/2 -z-10 size-[40rem] -translate-x-1/2 -translate-y-1/2 rounded-full bg-brand-600/25 blur-3xl"
                />
                <SkylineIllustration
                    class="absolute inset-x-0 bottom-0 -z-10 h-44 w-full sm:h-56"
                />
            </template>
            <div
                class="absolute inset-0 -z-10 bg-gradient-to-b from-navy-950/30 via-navy-950/20 to-navy-950/60"
            />

            <div
                class="mx-auto flex h-full max-w-4xl flex-col items-center justify-center px-6 pb-16 text-center"
            >
                <h1
                    class="font-serif text-3xl leading-tight font-semibold text-white drop-shadow sm:text-5xl"
                >
                    {{ slide.title }}
                </h1>
                <p
                    v-if="slide.subtitle"
                    class="mt-4 font-serif text-lg text-white/85 italic sm:text-2xl"
                >
                    {{ slide.subtitle }}
                </p>
                <component
                    :is="slide.external ? 'a' : Link"
                    :href="slide.href"
                    :target="slide.external ? '_blank' : undefined"
                    :rel="slide.external ? 'noopener noreferrer' : undefined"
                    :tabindex="index === active ? undefined : -1"
                    class="mt-7 inline-flex h-11 items-center gap-2 rounded-full bg-navy-900/90 px-6 text-sm font-semibold ring-1 ring-white/25 backdrop-blur transition-colors hover:bg-brand-600"
                >
                    {{ slide.buttonText }}
                    <ArrowRight class="size-4" />
                </component>
            </div>
        </div>

        <template v-if="slides.length > 1">
            <button
                type="button"
                class="absolute top-1/2 left-4 flex size-10 -translate-y-1/2 items-center justify-center rounded-md border border-white/40 bg-navy-950/30 backdrop-blur transition-colors hover:bg-white/15"
                aria-label="Oldingi slayd"
                @click="go(active - 1)"
            >
                <ChevronLeft class="size-5" />
            </button>
            <button
                type="button"
                class="absolute top-1/2 right-4 flex size-10 -translate-y-1/2 items-center justify-center rounded-md border border-white/40 bg-navy-950/30 backdrop-blur transition-colors hover:bg-white/15"
                aria-label="Keyingi slayd"
                @click="go(active + 1)"
            >
                <ChevronRight class="size-5" />
            </button>
            <div class="absolute bottom-6 left-1/2 flex -translate-x-1/2 gap-2">
                <button
                    v-for="(slide, index) in slides"
                    :key="slide.key"
                    type="button"
                    :class="[
                        'h-2 rounded-full transition-all',
                        index === active
                            ? 'w-6 bg-white'
                            : 'w-2 bg-white/45 hover:bg-white/70',
                    ]"
                    :aria-label="`${index + 1}-slayd`"
                    :aria-current="index === active ? 'true' : undefined"
                    @click="go(index)"
                />
            </div>
        </template>
    </section>
</template>
