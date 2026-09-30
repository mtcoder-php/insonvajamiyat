<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ArticleCard from '@/components/web/ArticleCard.vue';
import IssueCover from '@/components/web/IssueCover.vue';
import PageIntro from '@/components/web/PageIntro.vue';
import type { ArticleCard as ArticleCardData, IssueCard } from '@/types';

/**
 * Jurnal soni sahifasi — vaqtinchalik (to'liq dizayn: "web jurnal sonlari.png").
 */
defineProps<{
    issue: IssueCard;
    articles: ArticleCardData[];
}>();
</script>

<template>
    <Head :title="issue.label" />

    <PageIntro :title="issue.label" :description="issue.title ?? undefined">
        <div class="grid gap-8 lg:grid-cols-[12rem_1fr]">
            <IssueCover
                :src="issue.coverUrl"
                :number="issue.number"
                :year="issue.year"
                class="w-48"
            />
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                <ArticleCard
                    v-for="article in articles"
                    :key="article.id"
                    :article="article"
                />
            </div>
        </div>
    </PageIntro>
</template>
