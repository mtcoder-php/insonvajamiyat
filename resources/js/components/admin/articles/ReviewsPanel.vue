<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Download, Hourglass, Lock, Star, UsersRound, X } from '@lucide/vue';
import { ref } from 'vue';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { EditorialArticle, EditorialReview } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Muharrir kartasidagi "Taqrizchilar" tabi: raundlar bo'yicha taqrizlar, baholar va izohlar.
 */
defineProps<{ article: EditorialArticle }>();

const emit = defineEmits<{ invite: [] }>();

const tint: Record<string, string> = {
    invited: 'bg-amber-50 text-amber-700 ring-amber-200',
    accepted: 'bg-sky-50 text-sky-700 ring-sky-200',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    declined: 'bg-red-50 text-red-700 ring-red-200',
    cancelled: 'bg-navy-50 text-navy-500 ring-navy-200',
};

const recommendationTint: Record<string, string> = {
    accept: 'text-emerald-700',
    minor_revision: 'text-sky-700',
    major_revision: 'text-amber-700',
    reject: 'text-red-700',
};

const cancelling = ref<number | null>(null);

function cancel(review: EditorialReview): void {
    if (!review.cancelUrl) {
        return;
    }

    cancelling.value = review.id;
    router.delete(review.cancelUrl, {
        preserveScroll: true,
        onFinish: () => (cancelling.value = null),
    });
}
</script>

<template>
    <div class="grid gap-3">
        <div
            class="flex items-start gap-3 rounded-lg bg-[#f5f8fc] px-3 py-2.5 text-xs leading-relaxed text-navy-600"
        >
            <Lock class="mt-0.5 size-4 shrink-0 text-brand-600" />
            Blind review: taqrizchilar ismi muallifga ko'rsatilmaydi. Muallif
            faqat "Muallifga izoh" va baholarni ko'radi (qaror chiqqandan
            keyin).
        </div>

        <article
            v-for="review in article.reviews"
            :key="review.id"
            class="rounded-lg border border-line p-4 transition-shadow hover:shadow-[0_12px_26px_-20px_rgba(0,36,66,0.5)]"
        >
            <header class="flex flex-wrap items-center gap-2">
                <span class="text-[13px] font-semibold text-navy-900">{{
                    review.reviewer
                }}</span>
                <span class="text-[11px] text-navy-400">{{
                    t(':number-raund', { number: review.round })
                }}</span>
                <span
                    :class="
                        cn(
                            'rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset',
                            tint[review.status],
                        )
                    "
                    >{{ review.statusLabel }}</span
                >
                <span
                    v-if="review.daysLeft !== null"
                    :class="
                        cn(
                            'inline-flex items-center gap-1 text-[11px]',
                            review.daysLeft < 0
                                ? 'font-semibold text-red-600'
                                : review.daysLeft <= 3
                                  ? 'text-amber-600'
                                  : 'text-navy-500',
                        )
                    "
                >
                    <Hourglass class="size-3" />
                    {{
                        review.daysLeft < 0
                            ? t(':daysLeft kun kechikdi', {
                                  daysLeft: Math.abs(review.daysLeft),
                              })
                            : t(':daysLeft kun qoldi', {
                                  daysLeft: review.daysLeft,
                              })
                    }}
                </span>
                <span class="ml-auto text-[11px] text-navy-400 tabular-nums">
                    {{ formatDate(review.invitedAt) }}
                </span>
                <button
                    v-if="review.cancelUrl"
                    type="button"
                    class="rounded p-1 text-navy-400 transition-colors hover:bg-red-50 hover:text-red-600 disabled:opacity-40"
                    :disabled="cancelling === review.id"
                    :title="t('Taklifni bekor qilish')"
                    @click="cancel(review)"
                >
                    <X class="size-4" />
                </button>
            </header>

            <template v-if="review.status === 'completed'">
                <p class="mt-3 flex flex-wrap items-center gap-3 text-[13px]">
                    <span
                        v-if="review.score !== null"
                        class="inline-flex items-center gap-1 font-semibold text-navy-900"
                    >
                        <Star class="size-4 fill-gold-400 text-gold-500" />
                        {{ review.score.toFixed(1) }} / 5
                    </span>
                    <span
                        v-if="review.recommendationLabel"
                        :class="
                            cn(
                                'font-semibold',
                                recommendationTint[review.recommendation ?? ''],
                            )
                        "
                    >
                        {{ review.recommendationLabel }}
                    </span>
                </p>
                <dl class="mt-3 grid gap-1.5 sm:grid-cols-2">
                    <div
                        v-for="criterion in review.criteria"
                        :key="criterion.label"
                        class="flex items-center gap-2 text-xs"
                    >
                        <dt class="w-36 shrink-0 truncate text-navy-600">
                            {{ criterion.label }}
                        </dt>
                        <dd class="flex flex-1 items-center gap-2">
                            <span
                                class="h-1.5 flex-1 overflow-hidden rounded-full bg-navy-50"
                            >
                                <span
                                    class="block h-full rounded-full bg-brand-500"
                                    :style="{
                                        width: `${((criterion.value ?? 0) / 5) * 100}%`,
                                    }"
                                />
                            </span>
                            <span
                                class="w-6 text-right font-semibold text-navy-800 tabular-nums"
                            >
                                {{ criterion.value?.toFixed(1) ?? '—' }}
                            </span>
                        </dd>
                    </div>
                </dl>
                <div v-if="review.commentsToAuthor" class="mt-3">
                    <p class="text-[11px] font-semibold text-navy-500">
                        {{ t('Muallifga izoh') }}
                    </p>
                    <p
                        class="mt-1 rounded-lg bg-[#f8fafd] px-3 py-2 text-[13px] leading-relaxed whitespace-pre-line text-navy-700"
                    >
                        {{ review.commentsToAuthor }}
                    </p>
                </div>
                <div v-if="review.commentsToEditor" class="mt-2">
                    <p
                        class="flex items-center gap-1 text-[11px] font-semibold text-navy-500"
                    >
                        <Lock class="size-3" />
                        {{ t('Muharrir uchun maxfiy izoh') }}
                    </p>
                    <p
                        class="mt-1 rounded-lg bg-amber-50/60 px-3 py-2 text-[13px] leading-relaxed whitespace-pre-line text-navy-700"
                    >
                        {{ review.commentsToEditor }}
                    </p>
                </div>
                <a
                    v-if="review.attachmentUrl"
                    :href="review.attachmentUrl"
                    class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 hover:underline"
                >
                    <Download class="size-3.5" />
                    {{ t('Taqriz fayli') }}
                </a>
            </template>
            <p
                v-else-if="
                    review.status === 'declined' && review.commentsToEditor
                "
                class="mt-2 text-xs text-navy-500"
            >
                {{ t('Sabab: :reason', { reason: review.commentsToEditor }) }}
            </p>
        </article>

        <div
            v-if="!article.reviews.length"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-navy-200 bg-[#f8fafd] px-6 py-10 text-center"
        >
            <span
                class="flex size-12 items-center justify-center rounded-full bg-brand-50 text-brand-600"
            >
                <UsersRound class="size-6" />
            </span>
            <p class="text-sm font-semibold text-navy-900">
                {{ t('Taqrizchilar hali tayinlanmagan') }}
            </p>
            <button
                v-if="article.can.invite"
                type="button"
                class="rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white transition-all hover:-translate-y-0.5 hover:bg-brand-700"
                @click="emit('invite')"
            >
                {{ t('Taqrizchilarni tayinlash') }}
            </button>
        </div>
    </div>
</template>
