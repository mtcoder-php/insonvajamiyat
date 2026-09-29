<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CalendarDays, Fingerprint, UserRound } from '@lucide/vue';
import PageIntro from '@/components/web/PageIntro.vue';
import { formatDate } from '@/lib/format';
import type { ArticleCard } from '@/types';

/**
 * Maqola sahifasi — vaqtinchalik (to'liq dizayn: "web maqola view page.png").
 */
defineProps<{
    article: ArticleCard & { abstract: string | null };
}>();
</script>

<template>
    <Head :title="article.title" />

    <PageIntro :title="article.title" :description="article.subject?.name">
        <div class="max-w-3xl space-y-4 surface-card p-6">
            <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-navy-600">
                <span
                    v-if="article.authors"
                    class="inline-flex items-center gap-1.5"
                >
                    <UserRound class="size-4 text-brand-600" />
                    {{ article.authors }}
                </span>
                <span
                    v-if="article.publishedAt"
                    class="inline-flex items-center gap-1.5"
                >
                    <CalendarDays class="size-4 text-brand-600" />
                    {{ formatDate(article.publishedAt) }}
                </span>
                <span
                    v-if="article.doi"
                    class="inline-flex items-center gap-1.5"
                >
                    <Fingerprint class="size-4 text-brand-600" />
                    {{ article.doi }}
                </span>
            </div>
            <div v-if="article.abstract">
                <h2 class="mb-2 font-serif text-lg font-semibold text-navy-950">
                    Annotatsiya
                </h2>
                <p class="text-sm leading-relaxed text-navy-700">
                    {{ article.abstract }}
                </p>
            </div>
        </div>
    </PageIntro>
</template>
