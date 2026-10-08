<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { HeroSlide } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Bosh sahifa slayderi (home.png): slaydlar avtomatik almashadi,
 * strelkalar, nuqtalar (progress bilan), klaviatura va surish (swipe).
 *
 * Slaydlar: admin bannerlari yoki config('journal.hero_slides').
 * Rasm yo'q slayd brend fonida chiqadi.
 *
 * Qulaylik: sahifaning h1 sarlavhasi Home.vue'da (slayd sarlavhalari — h2, almashganda yo'qolmaydi);
 * sichqoncha ustida yoki klaviatura fokusida slayder to'xtaydi; "harakatni kamaytirish"
 * sozlamasi yoqilgan qurilmalarda avtomatik almashmaydi; fokusda ko'rinadigan halqa.
 */
const props = defineProps<{
    slides: HeroSlide[];
}>();

const INTERVAL = 7000;

const items = computed(() =>
    props.slides.map((slide) => ({ key: slide.key, slide })),
);

const journal = computed(() => usePage().props.journal);
const issn = computed(() =>
    journal.value.issn ? `ISSN ${journal.value.issn}` : null,
);

// Banner havolasi boshqa saytga olib borsa — oddiy <a>, aks holda Inertia Link
function isExternal(url: string): boolean {
    try {
        return (
            new URL(url, window.location.href).origin !== window.location.origin
        );
    } catch {
        return false;
    }
}

const active = ref(0);
const paused = ref(false);
const reduceMotion = ref(false);
let timer: ReturnType<typeof setTimeout> | undefined;

const count = computed(() => items.value.length);
const autoplay = computed(
    () => count.value > 1 && !paused.value && !reduceMotion.value,
);

function go(index: number): void {
    if (count.value === 0) {
        return;
    }

    active.value = (index + count.value) % count.value;
}

const next = (): void => go(active.value + 1);
const prev = (): void => go(active.value - 1);

function schedule(): void {
    clearTimeout(timer);

    if (autoplay.value) {
        timer = setTimeout(next, INTERVAL);
    }
}

// Slayd almashganda yoki pauza tugaganda taymer qaytadan boshlanadi
watch([active, autoplay], schedule);

// Sensorli ekranda surish
let pointerStartX: number | null = null;

function onPointerDown(event: PointerEvent): void {
    pointerStartX = event.clientX;
}

function onPointerUp(event: PointerEvent): void {
    if (pointerStartX === null) {
        return;
    }

    const delta = event.clientX - pointerStartX;
    pointerStartX = null;

    if (Math.abs(delta) > 50) {
        if (delta < 0) {
            next();
        } else {
            prev();
        }
    }
}

onMounted(() => {
    reduceMotion.value = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;
    schedule();
});

onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <section
        v-if="count"
        class="relative isolate h-[32rem] overflow-hidden bg-navy-950 text-white outline-none select-none focus-visible:ring-4 focus-visible:ring-gold-300/70 focus-visible:ring-inset sm:h-[34rem] lg:h-[38rem]"
        aria-roledescription="carousel"
        :aria-label="t('Asosiy slayder')"
        tabindex="0"
        @mouseenter="paused = true"
        @mouseleave="paused = false"
        @focusin="paused = true"
        @focusout="paused = false"
        @keydown.left.prevent="prev"
        @keydown.right.prevent="next"
        @pointerdown="onPointerDown"
        @pointerup="onPointerUp"
    >
        <div
            v-for="(item, index) in items"
            :key="item.key"
            :class="[
                'absolute inset-0 transition-opacity duration-1000 ease-out',
                index === active
                    ? 'z-10 opacity-100'
                    : 'pointer-events-none opacity-0',
            ]"
            role="group"
            aria-roledescription="slide"
            :aria-label="`${index + 1} / ${count}`"
            :aria-hidden="index !== active"
        >
            <!-- Fon -->
            <template v-if="item.slide.imageUrl">
                <img
                    :src="item.slide.imageUrl"
                    alt=""
                    :loading="index === 0 ? 'eager' : 'lazy'"
                    :fetchpriority="index === 0 ? 'high' : 'auto'"
                    :class="[
                        'absolute inset-0 -z-10 size-full object-cover transition-transform duration-[8000ms] ease-out',
                        index === active ? 'scale-100' : 'scale-110',
                    ]"
                />
                <div class="absolute inset-0 -z-10 bg-navy-950/45" />
                <div
                    class="absolute inset-0 -z-10 bg-gradient-to-t from-navy-950/85 via-navy-950/10 to-navy-950/30"
                />
            </template>
            <template v-else>
                <div class="absolute inset-0 -z-10 bg-navy-gradient" />
                <div class="absolute inset-0 -z-10 bg-girih opacity-[0.05]" />
                <div
                    :class="[
                        'absolute -z-10 size-[36rem] rounded-full blur-3xl',
                        index % 2 === 0
                            ? 'top-1/2 right-[10%] -translate-y-1/2 bg-brand-500/25'
                            : 'top-1/3 left-[8%] bg-brand-400/15',
                    ]"
                />
            </template>

            <!-- Slayd matni (markazda) -->
            <div
                class="mx-auto flex h-full max-w-4xl flex-col items-center justify-center px-6 pb-16 text-center"
            >
                <p
                    :class="[
                        'flex items-center gap-3 text-xs font-semibold tracking-[0.2em] text-gold-300 uppercase transition-all delay-150 duration-700',
                        index === active
                            ? 'translate-y-0 opacity-100'
                            : 'translate-y-4 opacity-0',
                    ]"
                >
                    <span class="h-px w-8 bg-gold-400" aria-hidden="true" />
                    <span
                        >{{ t('Ilmiy-nazariy jurnal')
                        }}<span v-if="issn" class="hidden sm:inline"
                            >&nbsp;·&nbsp;{{ issn }}</span
                        ></span
                    >
                    <span class="h-px w-8 bg-gold-400" aria-hidden="true" />
                </p>
                <component
                    is="h2"
                    :class="[
                        'mt-6 font-serif text-4xl leading-[1.12] font-semibold text-balance text-white drop-shadow-sm transition-all delay-300 duration-700 sm:text-5xl lg:text-6xl',
                        index === active
                            ? 'translate-y-0 opacity-100'
                            : 'translate-y-6 opacity-0',
                    ]"
                >
                    {{ item.slide.title }}
                </component>
                <p
                    v-if="item.slide.subtitle"
                    :class="[
                        'mt-5 max-w-2xl font-serif text-lg text-balance text-white/85 italic transition-all delay-500 duration-700 sm:text-xl lg:text-2xl',
                        index === active
                            ? 'translate-y-0 opacity-100'
                            : 'translate-y-6 opacity-0',
                    ]"
                >
                    {{ item.slide.subtitle }}
                </p>
                <component
                    :is="isExternal(item.slide.linkUrl) ? 'a' : Link"
                    v-if="item.slide.linkUrl && item.slide.buttonText"
                    :href="item.slide.linkUrl"
                    :rel="
                        isExternal(item.slide.linkUrl)
                            ? 'noopener noreferrer'
                            : undefined
                    "
                    :tabindex="index === active ? undefined : -1"
                    :class="[
                        'mt-9 inline-flex h-12 items-center gap-2 rounded-lg bg-brand-600 px-7 text-sm font-semibold text-white shadow-lg shadow-black/25 transition-all delay-700 duration-700 hover:bg-brand-500',
                        index === active
                            ? 'translate-y-0 opacity-100'
                            : 'translate-y-6 opacity-0',
                    ]"
                >
                    {{ item.slide.buttonText }}
                    <ArrowRight class="size-4" />
                </component>
            </div>
        </div>

        <!-- Boshqaruv: strelkalar pastki burchaklarda, o'rtada nuqtalar (home.png) -->
        <template v-if="count > 1">
            <button
                type="button"
                class="absolute bottom-5 left-4 z-20 flex size-10 items-center justify-center rounded-lg border-2 border-white/80 bg-black/10 text-white backdrop-blur-sm transition-colors hover:bg-white hover:text-navy-900 sm:bottom-8 sm:left-8 sm:size-12"
                :aria-label="t('Oldingi slayd')"
                @click="prev"
            >
                <ChevronLeft class="size-5" />
            </button>
            <button
                type="button"
                class="absolute right-4 bottom-5 z-20 flex size-10 items-center justify-center rounded-lg border-2 border-white/80 bg-black/10 text-white backdrop-blur-sm transition-colors hover:bg-white hover:text-navy-900 sm:right-8 sm:bottom-8 sm:size-12"
                :aria-label="t('Keyingi slayd')"
                @click="next"
            >
                <ChevronRight class="size-5" />
            </button>

            <div
                class="absolute bottom-7 left-1/2 z-20 flex -translate-x-1/2 items-center gap-1 sm:bottom-11"
                role="group"
                :aria-label="t('Slaydlar')"
            >
                <button
                    v-for="(item, index) in items"
                    :key="item.key"
                    type="button"
                    class="group flex size-6 cursor-pointer items-center justify-center rounded-full focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                    :aria-label="t(':number-slayd', { number: index + 1 })"
                    :aria-current="index === active ? 'true' : undefined"
                    @click="go(index)"
                >
                    <span
                        :class="[
                            'block size-2.5 rounded-full border-2 transition-all duration-300',
                            index === active
                                ? 'scale-110 border-white bg-white shadow-[0_0_0_3px_rgba(255,255,255,0.25)]'
                                : 'border-white/80 bg-transparent group-hover:bg-white/50',
                        ]"
                    />
                </button>
            </div>
        </template>
    </section>
</template>
