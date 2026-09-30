<script setup lang="ts">
import HomeCard from '@/components/web/home/HomeCard.vue';
import NewsletterForm from '@/components/web/NewsletterForm.vue';

/**
 * "Yangiliklardan xabardor bo'ling" — to'q ko'k obuna kartasi.
 */
</script>

<template>
    <HomeCard tone="navy" title="Yangiliklardan xabardor bo'ling">
        <p class="-mt-2 mb-4 text-xs leading-relaxed text-white/75">
            Jurnal yangiliklari va yangi sonlar haqida xabardor bo'lish uchun
            obuna bo'ling.
        </p>
        <NewsletterForm id="home-newsletter-email" compact />
    </HomeCard>
</template>
