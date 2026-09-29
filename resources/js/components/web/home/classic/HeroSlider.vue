<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import HeroScene from '@/components/web/home/classic/HeroScene.vue';
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
        class="relative isolate h-[24rem] overflow-hidden bg-[#dcecf7] sm:h-[28rem] lg:h-[30rem]"
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
            <template v-if="slide.imageUrl">
                <img
                    :src="slide.imageUrl"
                    alt=""
                    class="absolute inset-0 -z-10 size-full object-cover"
                    :loading="index === 0 ? 'eager' : 'lazy'"
                />
                <!-- Matn o'qilishi uchun markazda yumshoq oq nur -->
                <div
                    class="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_center,rgba(255,255,255,0.82)_0%,rgba(255,255,255,0.45)_38%,rgba(255,255,255,0)_70%)]"
                />
            </template>
            <HeroScene v-else class="absolute inset-0 -z-10 size-full" />

            <div
                class="mx-auto flex h-full max-w-3xl flex-col items-center justify-center px-6 pb-10 text-center"
            >
                <h1
                    class="font-serif text-3xl leading-tight font-bold text-navy-900 sm:text-5xl lg:text-[3.4rem]"
                >
                    {{ slide.title }}
                </h1>
                <p
                    v-if="slide.subtitle"
                    class="mt-4 font-serif text-lg text-navy-800 italic sm:text-2xl"
                >
                    {{ slide.subtitle }}
                </p>
                <component
                    :is="slide.external ? 'a' : Link"
                    :href="slide.href"
                    :target="slide.external ? '_blank' : undefined"
                    :rel="slide.external ? 'noopener noreferrer' : undefined"
                    :tabindex="index === active ? undefined : -1"
                    class="mt-7 inline-flex h-12 items-center gap-2 rounded-full bg-navy-800 px-7 text-sm font-semibold text-white shadow-lg shadow-navy-900/20 transition-colors hover:bg-brand-600"
                >
                    {{ slide.buttonText }}
                    <ArrowRight class="size-4" />
                </component>
            </div>
        </div>

        <template v-if="slides.length > 1">
            <button
                type="button"
                class="absolute top-1/2 left-4 flex size-11 -translate-y-1/2 items-center justify-center rounded-lg border border-white bg-white/70 text-navy-800 shadow-sm backdrop-blur transition-colors hover:bg-white"
                aria-label="Oldingi slayd"
                @click="go(active - 1)"
            >
                <ChevronLeft class="size-5" />
            </button>
            <button
                type="button"
                class="absolute top-1/2 right-4 flex size-11 -translate-y-1/2 items-center justify-center rounded-lg border border-white bg-white/70 text-navy-800 shadow-sm backdrop-blur transition-colors hover:bg-white"
                aria-label="Keyingi slayd"
                @click="go(active + 1)"
            >
                <ChevronRight class="size-5" />
            </button>
            <div class="absolute bottom-5 left-1/2 flex -translate-x-1/2 gap-2">
                <button
                    v-for="(slide, index) in slides"
                    :key="slide.key"
                    type="button"
                    :class="[
                        'h-2.5 rounded-full transition-all',
                        index === active
                            ? 'w-7 bg-navy-800'
                            : 'w-2.5 bg-white ring-1 ring-navy-800/30 hover:bg-navy-200',
                    ]"
                    :aria-label="`${index + 1}-slayd`"
                    :aria-current="index === active ? 'true' : undefined"
                    @click="go(index)"
                />
            </div>
        </template>
    </section>
</template>
