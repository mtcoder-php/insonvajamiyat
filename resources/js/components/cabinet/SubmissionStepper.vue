<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, ListOrdered } from '@lucide/vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import { cn } from '@/lib/utils';
import { create } from '@/routes/cabinet/articles';

/**
 * "Yangi maqola yuborish jarayoni" — 7 bosqich (forma: cabinet/articles/Create).
 * current — joriy bosqich (dashboardda 1).
 */
const { current = 1 } = defineProps<{ current?: number }>();

const steps = [
    "Maqola ma'lumotlari",
    'Mualliflar',
    'Annotatsiya',
    "Kalit so'zlar",
    'Fayllar',
    'Tekshirish',
    'Yuborish',
];
</script>

<template>
    <DashCard>
        <header class="mb-5 flex items-center justify-between gap-3">
            <h2
                class="flex items-center gap-2 font-sans text-[15px] font-bold text-navy-950"
            >
                <ListOrdered class="size-[18px] text-brand-600" />
                Yangi maqola yuborish jarayoni
            </h2>
            <Link
                :href="create()"
                class="group inline-flex items-center gap-1 text-xs font-medium text-brand-700 hover:text-brand-600"
            >
                Boshlash
                <ArrowRight
                    class="size-3.5 transition-transform group-hover:translate-x-0.5"
                />
            </Link>
        </header>

        <div class="-mx-1 overflow-x-auto px-1 pb-1">
            <ol class="flex min-w-[640px] items-start">
                <li
                    v-for="(label, i) in steps"
                    :key="label"
                    class="group/step relative flex flex-1 flex-col items-center gap-2 text-center"
                >
                    <span
                        v-if="i > 0"
                        :class="
                            cn(
                                'absolute top-4 right-1/2 h-px w-full -translate-y-1/2',
                                i < current ? 'bg-brand-500' : 'bg-line',
                            )
                        "
                        aria-hidden="true"
                    />
                    <Link
                        :href="create()"
                        :class="
                            cn(
                                'relative z-10 flex size-8 items-center justify-center rounded-full text-[13px] font-semibold tabular-nums transition-all duration-300 group-hover/step:-translate-y-0.5 group-hover/step:scale-110',
                                i + 1 === current
                                    ? 'bg-brand-600 text-white shadow-[0_0_0_4px_rgba(0,108,246,0.15)]'
                                    : i + 1 < current
                                      ? 'bg-brand-100 text-brand-700'
                                      : 'border border-line bg-white text-navy-500 group-hover/step:border-brand-300 group-hover/step:text-brand-700',
                            )
                        "
                        :aria-label="`${i + 1}-bosqich: ${label}`"
                        :aria-current="i + 1 === current ? 'step' : undefined"
                    >
                        {{ i + 1 }}
                    </Link>
                    <span
                        :class="
                            cn(
                                'text-xs transition-colors',
                                i + 1 === current
                                    ? 'font-semibold text-navy-900'
                                    : 'text-navy-500 group-hover/step:text-brand-700',
                            )
                        "
                    >
                        {{ label }}
                    </span>
                </li>
            </ol>
        </div>
    </DashCard>
</template>
