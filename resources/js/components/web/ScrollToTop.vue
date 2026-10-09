<script setup lang="ts">
import { ArrowUp } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { t } from '@/lib/i18n';

/**
 * "Yuqoriga" suzuvchi tugmasi: sahifa pastga aylantirilganda o'ng pastki burchakda chiqadi.
 * Atrofidagi oltin halqa sahifaning qancha qismi o'qilganini ko'rsatadi.
 */
const SHOW_AFTER = 400; // px

const progress = ref(0); // 0..1
const visible = ref(false);
let frame = 0;

// Halqa: r = 25, aylana uzunligi ≈ 157.08
const radius = 25;
const circumference = 2 * Math.PI * radius;
const dashOffset = computed(() => circumference * (1 - progress.value));
const percent = computed(() => Math.round(progress.value * 100));

function measure(): void {
    frame = 0;
    const scrolled = window.scrollY;
    const max = document.documentElement.scrollHeight - window.innerHeight;

    progress.value = max > 0 ? Math.min(1, Math.max(0, scrolled / max)) : 0;
    visible.value = scrolled > SHOW_AFTER;
}

function onScroll(): void {
    if (!frame) {
        frame = window.requestAnimationFrame(measure);
    }
}

function scrollToTop(): void {
    const reduce = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;

    window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
}

onMounted(() => {
    measure();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
    window.removeEventListener('resize', onScroll);

    if (frame) {
        window.cancelAnimationFrame(frame);
    }
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-6 scale-75 opacity-0"
        leave-active-class="transition duration-200 ease-in"
        leave-to-class="translate-y-6 scale-75 opacity-0"
    >
        <button
            v-show="visible"
            type="button"
            :aria-label="t('Yuqoriga')"
            :title="`${t('Yuqoriga')} · ${percent}%`"
            class="group fixed right-4 bottom-5 z-40 flex size-14 items-center justify-center rounded-full bg-white/90 shadow-[0_10px_30px_-10px_rgba(0,30,60,0.45)] ring-1 ring-navy-950/5 backdrop-blur transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_18px_40px_-12px_rgba(196,154,69,0.65)] focus-visible:ring-4 focus-visible:ring-brand-200 focus-visible:outline-none active:translate-y-0 active:scale-95 sm:right-6 sm:bottom-6 lg:right-8 lg:bottom-8"
            @click="scrollToTop"
        >
            <!-- O'qilganlik halqasi -->
            <svg
                viewBox="0 0 56 56"
                class="pointer-events-none absolute inset-0 size-14 -rotate-90"
                aria-hidden="true"
            >
                <defs>
                    <linearGradient id="stt-ring" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#f0ce7c" />
                        <stop offset="100%" stop-color="#c49a45" />
                    </linearGradient>
                </defs>
                <circle
                    cx="28"
                    cy="28"
                    :r="radius"
                    fill="none"
                    stroke-width="3"
                    class="stroke-navy-950/10"
                />
                <circle
                    cx="28"
                    cy="28"
                    :r="radius"
                    fill="none"
                    stroke="url(#stt-ring)"
                    stroke-width="3"
                    stroke-linecap="round"
                    :stroke-dasharray="circumference"
                    :stroke-dashoffset="dashOffset"
                    class="transition-[stroke-dashoffset] duration-150 ease-out"
                />
            </svg>

            <!-- Ichki tugma -->
            <span
                class="relative flex size-10 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-navy-800 via-navy-900 to-navy-950 text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.15),0_6px_14px_-6px_rgba(0,30,60,0.7)] transition-colors duration-300 group-hover:from-gold-400 group-hover:via-gold-500 group-hover:to-gold-600 group-hover:text-navy-950"
            >
                <ArrowUp
                    class="size-[18px] transition-transform duration-300 group-hover:-translate-y-0.5 group-hover:animate-bounce motion-reduce:group-hover:animate-none"
                    stroke-width="2.5"
                />
            </span>
        </button>
    </Transition>
</template>
