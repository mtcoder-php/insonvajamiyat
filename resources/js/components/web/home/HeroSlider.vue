<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import IssueCover from '@/components/web/IssueCover.vue';
import { formatDateLong } from '@/lib/format';
import type { HeroSlide, LatestIssue } from '@/types';

/**
 * Bosh sahifa slayderi (home.png): slaydlar avtomatik almashadi,
 * strelkalar, nuqtalar (progress bilan), klaviatura va surish (swipe).
 *
 * Slaydlar: admin bannerlari yoki config('journal.hero_slides');
 * oxirida joriy son slaydi. Rasm yo'q slayd brend fonida chiqadi.
 */
const props = defineProps<{
    slides: HeroSlide[];
    issue: LatestIssue | null;
}>();

const INTERVAL = 7000;

type Item =
    | { type: 'banner'; key: string; slide: HeroSlide }
    | { type: 'issue'; key: string; issue: LatestIssue };

const items = computed<Item[]>(() => {
    const list: Item[] = props.slides.map((slide) => ({
        type: 'banner',
        key: slide.key,
        slide,
    }));

    if (props.issue) {
        list.push({ type: 'issue', key: 'issue', issue: props.issue });
    }

    return list;
});

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
        class="relative isolate h-[32rem] overflow-hidden bg-navy-950 text-white outline-none select-none sm:h-[34rem] lg:h-[38rem]"
        aria-roledescription="carousel"
        aria-label="Asosiy slayder"
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
            <template v-if="item.type === 'banner' && item.slide.imageUrl">
                <img
                    :src="item.slide.imageUrl"
                    alt=""
                    :loading="index === 0 ? 'eager' : 'lazy'"
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

            <!-- Oddiy slayd: markazdagi matn -->
            <div
                v-if="item.type === 'banner'"
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
                        >Ilmiy-nazariy jurnal<span
                            v-if="issn"
                            class="hidden sm:inline"
                            >&nbsp;·&nbsp;{{ issn }}</span
                        ></span
                    >
                    <span class="h-px w-8 bg-gold-400" aria-hidden="true" />
                </p>
                <component
                    :is="index === 0 ? 'h1' : 'h2'"
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

            <!-- Joriy son slaydi -->
            <div
                v-else
                class="mx-auto grid h-full max-w-6xl items-center gap-10 px-6 pb-14 sm:px-10 md:grid-cols-[1fr_auto] lg:px-8"
            >
                <div
                    :class="[
                        'transition-all delay-200 duration-700',
                        index === active
                            ? 'translate-y-0 opacity-100'
                            : 'translate-y-6 opacity-0',
                    ]"
                >
                    <p
                        class="flex items-center gap-3 text-xs font-semibold tracking-[0.2em] text-gold-300 uppercase"
                    >
                        <span class="h-px w-8 bg-gold-400" aria-hidden="true" />
                        Joriy son
                    </p>
                    <h2
                        class="mt-5 font-serif text-4xl leading-tight font-semibold text-white sm:text-5xl"
                    >
                        {{ item.issue.label }}
                    </h2>
                    <p
                        class="mt-3 max-w-xl font-serif text-xl text-white/85 italic"
                    >
                        {{
                            item.issue.title ||
                            `"${journal.name}" ilmiy jurnali`
                        }}
                    </p>
                    <p class="mt-4 text-sm text-white/65">
                        <template v-if="item.issue.publishedAt">
                            {{ formatDateLong(item.issue.publishedAt) }}
                        </template>
                        <template v-if="item.issue.articlesCount">
                            · {{ item.issue.articlesCount }} ta maqola
                        </template>
                        <template v-if="item.issue.pagesTotal">
                            · {{ item.issue.pagesTotal }} bet
                        </template>
                    </p>
                    <Link
                        :href="item.issue.url"
                        :tabindex="index === active ? undefined : -1"
                        class="mt-8 inline-flex h-12 items-center gap-2 rounded-lg bg-brand-600 px-7 text-sm font-semibold text-white shadow-lg shadow-black/25 transition-colors hover:bg-brand-500"
                    >
                        Sonni ko'rish
                        <ArrowRight class="size-4" />
                    </Link>
                </div>
                <Link
                    :href="item.issue.url"
                    :tabindex="-1"
                    aria-hidden="true"
                    :class="[
                        'hidden w-56 transition-all delay-300 duration-700 md:block lg:w-64',
                        index === active
                            ? 'translate-x-0 rotate-0 opacity-100'
                            : 'translate-x-8 rotate-3 opacity-0',
                    ]"
                >
                    <IssueCover
                        :src="item.issue.coverUrl"
                        :number="item.issue.number"
                        :year="item.issue.year"
                        class="shadow-2xl shadow-black/50"
                    />
                </Link>
            </div>
        </div>

        <!-- Boshqaruv: strelkalar pastki burchaklarda, o'rtada nuqtalar (home.png) -->
        <template v-if="count > 1">
            <button
                type="button"
                class="absolute bottom-5 left-4 z-20 flex size-10 items-center justify-center rounded-lg border-2 border-white/80 bg-black/10 text-white backdrop-blur-sm transition-colors hover:bg-white hover:text-navy-900 sm:bottom-8 sm:left-8 sm:size-12"
                aria-label="Oldingi slayd"
                @click="prev"
            >
                <ChevronLeft class="size-5" />
            </button>
            <button
                type="button"
                class="absolute right-4 bottom-5 z-20 flex size-10 items-center justify-center rounded-lg border-2 border-white/80 bg-black/10 text-white backdrop-blur-sm transition-colors hover:bg-white hover:text-navy-900 sm:right-8 sm:bottom-8 sm:size-12"
                aria-label="Keyingi slayd"
                @click="next"
            >
                <ChevronRight class="size-5" />
            </button>

            <div
                class="absolute bottom-7 left-1/2 z-20 flex -translate-x-1/2 items-center gap-1 sm:bottom-11"
                role="tablist"
                aria-label="Slaydlar"
            >
                <button
                    v-for="(item, index) in items"
                    :key="item.key"
                    type="button"
                    role="tab"
                    class="group flex size-6 cursor-pointer items-center justify-center"
                    :aria-label="`${index + 1}-slayd`"
                    :aria-selected="index === active"
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
