<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Spinner } from '@/components/ui/spinner';
import { subscribe } from '@/routes/newsletter';
import { t } from '@/lib/i18n';

/**
 * Yangiliklarga obuna formasi (to'q ko'k fon uchun).
 * Bir sahifada bir nechta bo'lishi mumkin — `id` har biri uchun alohida.
 */
withDefaults(
    defineProps<{
        id?: string;
        compact?: boolean;
    }>(),
    { id: 'newsletter-email', compact: false },
);
</script>

<template>
    <Form
        v-bind="subscribe.form()"
        :options="{ preserveScroll: true }"
        reset-on-success
        v-slot="{ errors, processing }"
        class="space-y-2"
    >
        <div
            class="flex overflow-hidden rounded-lg border border-white/20 bg-white/5 transition-all duration-300 focus-within:border-brand-400 focus-within:shadow-[0_0_0_4px_rgba(0,108,246,0.18)] hover:border-white/35"
        >
            <label :for="id" class="sr-only">{{
                t('Elektron pochta manzilingiz')
            }}</label>
            <input
                :id="id"
                name="email"
                type="email"
                required
                autocomplete="email"
                :placeholder="t('Email manzilingiz')"
                :class="[
                    'min-w-0 flex-1 bg-transparent px-4 text-sm text-white placeholder:text-white/45 focus:outline-none',
                    compact ? 'h-10' : 'h-11',
                ]"
            />
            <button
                type="submit"
                :disabled="processing"
                :class="[
                    'inline-flex shrink-0 items-center gap-2 bg-brand-600 px-4 text-sm font-semibold text-white transition-all duration-300 hover:bg-brand-500 hover:shadow-[inset_0_-2px_0_rgba(255,255,255,0.25)] disabled:opacity-60',
                    compact ? 'h-10' : 'h-11',
                ]"
            >
                <Spinner v-if="processing" />
                {{ t("Obuna bo'lish") }}
            </button>
        </div>
        <p v-if="errors.email" class="text-sm text-red-300">
            {{ errors.email }}
        </p>
    </Form>
</template>
