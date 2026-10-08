<script setup lang="ts">
import RichText from '@/components/web/content/RichText.vue';

/**
 * Statik sahifa bo'limi: oltin chiziqli serif sarlavha + tahririyat matni (yoki slot).
 */
defineProps<{
    id?: string;
    heading?: string;
    body?: string;
}>();
</script>

<template>
    <section
        :id="id"
        class="group scroll-mt-28 rounded-2xl border border-line bg-white p-6 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:border-brand-200 hover:shadow-[0_18px_40px_-28px_rgba(0,36,66,0.45)] sm:p-8"
    >
        <h2
            v-if="heading"
            class="mb-4 flex items-center gap-3 font-serif text-xl font-bold text-navy-950 sm:text-2xl"
        >
            <span
                class="h-7 w-1 shrink-0 rounded-full bg-gold-500 transition-all duration-300 group-hover:h-9 group-hover:bg-brand-600"
                aria-hidden="true"
            />
            {{ heading }}
        </h2>
        <RichText v-if="body" :text="body" />
        <slot />
    </section>
</template>
