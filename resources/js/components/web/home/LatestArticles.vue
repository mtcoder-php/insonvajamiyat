<script setup lang="ts">
import { FileText } from '@lucide/vue';
import ArticleCard from '@/components/web/ArticleCard.vue';
import HomeCard from '@/components/web/home/HomeCard.vue';
import { index as articlesIndex } from '@/routes/articles';
import type { ArticleCard as ArticleCardData } from '@/types';

/**
 * "So'nggi maqolalar" — 3 ustunli kartochkalar (home.png).
 */
defineProps<{ articles: ArticleCardData[] }>();
</script>

<template>
    <HomeCard
        title="So'nggi maqolalar"
        :href="articlesIndex()"
        link-text="Barcha maqolalar"
    >
        <div
            v-if="articles.length"
            class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3"
        >
            <ArticleCard
                v-for="article in articles"
                :key="article.id"
                :article="article"
            />
        </div>
        <div
            v-else
            class="flex flex-col items-center gap-2 py-10 text-center text-sm text-navy-500"
        >
            <FileText class="size-8 text-navy-300" />
            Hozircha nashr etilgan maqolalar yo'q.
        </div>
    </HomeCard>
</template>
