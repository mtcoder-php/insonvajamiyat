<script setup lang="ts">
import { ClipboardCheck, Star } from '@lucide/vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import { formatDate } from '@/lib/format';
import type { AuthorReview } from '@/types';

/**
 * Muallif uchun taqriz natijalari (anonim): baho, mezonlar va taqrizchi izohi.
 */
defineProps<{ reviews: AuthorReview[] }>();
</script>

<template>
    <DashCard>
        <h2
            class="mb-1 flex items-center gap-2 font-sans text-[15px] font-bold text-navy-950"
        >
            <ClipboardCheck class="size-[18px] text-brand-600" />
            Taqriz natijalari
        </h2>
        <p class="mb-4 text-xs text-navy-500">
            Taqrizchilar ismi ko'rsatilmaydi (blind review). Izohlar asosida
            maqolangizni takomillashtiring.
        </p>
        <div class="grid gap-3 lg:grid-cols-2">
            <article
                v-for="review in reviews"
                :key="review.id"
                class="rounded-xl border border-line p-4 transition-all hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-[0_14px_30px_-20px_rgba(0,36,66,0.45)]"
            >
                <header class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[13px] font-bold text-navy-900">
                            {{ review.label }}
                        </p>
                        <p class="text-[11px] text-navy-500">
                            {{ review.round }}-raund ·
                            {{ formatDate(review.completedAt) }}
                        </p>
                    </div>
                    <span
                        v-if="review.score !== null"
                        class="inline-flex items-center gap-1 rounded-lg bg-amber-50 px-2 py-1 text-sm font-bold text-amber-700 tabular-nums"
                    >
                        <Star class="size-3.5 fill-amber-400 text-amber-400" />
                        {{ review.score.toFixed(1) }}
                    </span>
                </header>
                <p
                    v-if="review.recommendation"
                    class="mt-2 inline-flex rounded-full bg-brand-50 px-2.5 py-0.5 text-[11px] font-semibold text-brand-700 ring-1 ring-brand-100 ring-inset"
                >
                    {{ review.recommendation }}
                </p>
                <dl class="mt-3 grid gap-1.5">
                    <div
                        v-for="criterion in review.criteria"
                        :key="criterion.label"
                        class="grid grid-cols-[1fr_5rem_2rem] items-center gap-2 text-xs"
                    >
                        <dt class="truncate text-navy-600">
                            {{ criterion.label }}
                        </dt>
                        <span
                            class="h-1.5 overflow-hidden rounded-full bg-navy-100"
                        >
                            <span
                                class="block h-full rounded-full bg-brand-500"
                                :style="{
                                    width: `${((criterion.value ?? 0) / 5) * 100}%`,
                                }"
                            />
                        </span>
                        <dd
                            class="text-right font-semibold text-navy-900 tabular-nums"
                        >
                            {{ criterion.value?.toFixed(1) ?? '—' }}
                        </dd>
                    </div>
                </dl>
                <p
                    v-if="review.comments"
                    class="mt-3 rounded-lg bg-[#f5f8fc] p-3 text-[13px] leading-relaxed whitespace-pre-line text-navy-700"
                >
                    {{ review.comments }}
                </p>
            </article>
        </div>
    </DashCard>
</template>
