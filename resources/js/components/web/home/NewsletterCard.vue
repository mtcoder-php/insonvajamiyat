<script setup lang="ts">
import HomeCard from '@/components/web/home/HomeCard.vue';
import NewsletterForm from '@/components/web/NewsletterForm.vue';
import { t } from '@/lib/i18n';

/**
 * "Yangiliklardan xabardor bo'ling" — to'q ko'k obuna kartasi.
 */
</script>

<template>
    <HomeCard tone="navy" :title="t('Yangiliklardan xabardor bo\'ling')">
        <p class="-mt-2 mb-4 text-xs leading-relaxed text-white/75">
            {{
                t(
                    "Jurnal yangiliklari va yangi sonlar haqida xabardor bo'lish uchun obuna bo'ling.",
                )
            }}
        </p>
        <NewsletterForm id="home-newsletter-email" compact />
    </HomeCard>
</template>
