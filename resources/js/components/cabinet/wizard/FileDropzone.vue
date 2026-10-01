<script setup lang="ts">
import { CloudUpload, LoaderCircle } from '@lucide/vue';
import { ref } from 'vue';
import { cn } from '@/lib/utils';
import type { FileLimit } from '@/types';

/**
 * Faylni tanlash yoki sudrab tashlash maydoni (yuklash jarayoni foizi bilan).
 */
const props = defineProps<{
    limit: FileLimit;
    title: string;
    disabled?: boolean;
    uploading?: boolean;
    progress?: number | null;
    error?: string;
}>();

const emit = defineEmits<{ select: [file: File] }>();

const input = ref<HTMLInputElement | null>(null);
const dragging = ref(false);

function pick(files: FileList | null | undefined): void {
    const file = files?.[0];

    if (file && !props.disabled && !props.uploading) {
        emit('select', file);
    }

    if (input.value) {
        input.value.value = '';
    }
}

function onDrop(event: DragEvent): void {
    dragging.value = false;
    pick(event.dataTransfer?.files);
}
</script>

<template>
    <div>
        <button
            type="button"
            :disabled="disabled || uploading"
            :class="
                cn(
                    'group relative flex w-full flex-col items-center gap-2 overflow-hidden rounded-xl border-2 border-dashed px-4 py-7 text-center transition-all duration-200',
                    dragging
                        ? 'scale-[1.01] border-brand-500 bg-brand-50'
                        : error
                          ? 'border-red-300 bg-red-50/40'
                          : 'border-navy-200 bg-[#f8fafd] hover:border-brand-400 hover:bg-brand-50/50',
                    (disabled || uploading) && 'cursor-not-allowed opacity-70',
                )
            "
            @click="input?.click()"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
        >
            <span
                class="flex size-12 items-center justify-center rounded-full bg-white text-brand-600 shadow-[0_6px_16px_-8px_rgba(0,36,66,0.4)] transition-transform duration-300 group-hover:-translate-y-0.5 group-hover:scale-105"
            >
                <LoaderCircle v-if="uploading" class="size-5 animate-spin" />
                <CloudUpload v-else class="size-5" />
            </span>
            <span class="text-sm font-semibold text-navy-900">
                {{ uploading ? 'Yuklanmoqda...' : title }}
            </span>
            <span class="text-xs text-navy-500">
                Faylni shu yerga tashlang yoki
                <span
                    class="font-semibold text-brand-700 underline-offset-2 group-hover:underline"
                    >kompyuterdan tanlang</span
                >
            </span>
            <span class="text-[11px] text-navy-400 uppercase">
                {{ limit.extensions.join(', ') }} ·
                {{ Math.round(limit.maxKb / 1024) }} MB gacha
            </span>
            <span
                v-if="uploading && progress != null"
                class="absolute inset-x-0 bottom-0 h-1 bg-brand-100"
            >
                <span
                    class="block h-full bg-brand-600 transition-[width] duration-200"
                    :style="{ width: `${progress}%` }"
                />
            </span>
        </button>
        <input
            ref="input"
            type="file"
            class="sr-only"
            :accept="limit.extensions.map((ext) => `.${ext}`).join(',')"
            @change="pick(($event.target as HTMLInputElement).files)"
        />
        <p v-if="error" class="mt-1.5 text-xs font-medium text-red-600">
            {{ error }}
        </p>
    </div>
</template>
