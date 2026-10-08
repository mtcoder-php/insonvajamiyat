<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Meta description — server (App\Support\Seo\SeoMeta) hisoblagan matn, shared prop orqali.
 * Layout'da turadi: Inertia server qo'ygan <meta data-inertia="description"> ni gidratatsiyada
 * o'chirib yubormasligi va sahifadan sahifaga o'tganda yangilanishi uchun.
 */
const page = usePage();
const description = computed(() => page.props.seoDescription ?? '');
</script>

<template>
    <Head>
        <meta
            v-if="description"
            head-key="description"
            name="description"
            :content="description"
        />
    </Head>
</template>
