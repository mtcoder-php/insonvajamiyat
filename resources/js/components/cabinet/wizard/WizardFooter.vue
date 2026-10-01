<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, LoaderCircle, Save } from '@lucide/vue';
import { primaryButtonClass, secondaryButtonClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';

/**
 * Bosqich pastidagi tugmalar: "Orqaga", "Qoralama sifatida saqlash", "Saqlash va davom etish".
 */
const { nextLabel = 'Saqlash va davom etish', showDraft = true } = defineProps<{
    prevHref?: string | null;
    processing?: boolean;
    nextLabel?: string;
    showDraft?: boolean;
    disabled?: boolean;
}>();

const emit = defineEmits<{ next: []; draft: [] }>();
</script>

<template>
    <div
        class="mt-6 flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:items-center sm:justify-between"
    >
        <Link
            v-if="prevHref"
            :href="prevHref"
            preserve-scroll
            :class="cn(secondaryButtonClass, 'group')"
        >
            <ArrowLeft
                class="size-4 transition-transform group-hover:-translate-x-0.5"
            />
            Orqaga
        </Link>
        <span v-else />

        <div class="flex flex-col-reverse gap-2 sm:flex-row">
            <button
                v-if="showDraft"
                type="button"
                :class="secondaryButtonClass"
                :disabled="processing || disabled"
                @click="emit('draft')"
            >
                <Save class="size-4" />
                Qoralama sifatida saqlash
            </button>
            <button
                type="button"
                :class="cn(primaryButtonClass, 'group')"
                :disabled="processing || disabled"
                @click="emit('next')"
            >
                <LoaderCircle v-if="processing" class="size-4 animate-spin" />
                {{ nextLabel }}
                <ArrowRight
                    v-if="!processing"
                    class="size-4 transition-transform group-hover:translate-x-0.5"
                />
            </button>
        </div>
    </div>
</template>
