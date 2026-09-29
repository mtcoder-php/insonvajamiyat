<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Spinner } from '@/components/ui/spinner';
import { subscribe } from '@/routes/newsletter';

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
            class="flex overflow-hidden rounded-lg border border-white/20 bg-white/5 focus-within:border-brand-400"
        >
            <label :for="id" class="sr-only">Elektron pochta manzilingiz</label>
            <input
                :id="id"
                name="email"
                type="email"
                required
                autocomplete="email"
                placeholder="Email manzilingiz"
                :class="[
                    'min-w-0 flex-1 bg-transparent px-4 text-sm text-white placeholder:text-white/45 focus:outline-none',
                    compact ? 'h-10' : 'h-11',
                ]"
            />
            <button
                type="submit"
                :disabled="processing"
                :class="[
                    'inline-flex shrink-0 items-center gap-2 bg-brand-600 px-4 text-sm font-semibold text-white transition-colors hover:bg-brand-500 disabled:opacity-60',
                    compact ? 'h-10' : 'h-11',
                ]"
            >
                <Spinner v-if="processing" />
                Obuna bo'lish
            </button>
        </div>
        <p v-if="errors.email" class="text-sm text-red-300">
            {{ errors.email }}
        </p>
    </Form>
</template>
