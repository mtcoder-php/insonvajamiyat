<script setup lang="ts">
import { CircleAlert, LoaderCircle, RotateCcw } from '@lucide/vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { AiRequestDetail } from '@/types';
import { studios } from './aiMeta';

/**
 * So'rov navbatda / bajarilmoqda (progress) yoki xato bilan tugagan holat.
 */
const props = defineProps<{ request: AiRequestDetail }>();

defineEmits<{ retry: [] }>();

const studio = computed(() => studios[props.request.type]);
const failed = computed(() => props.request.status === 'failed');
const percent = computed(() =>
    props.request.status === 'queued' ? 4 : Math.max(8, props.request.progress),
);
</script>

<template>
    <div
        :class="
            cn(
                'flex min-h-72 flex-col items-center justify-center gap-4 rounded-xl border px-6 py-10 text-center',
                failed
                    ? 'border-red-100 bg-red-50/40'
                    : 'border-brand-100 bg-gradient-to-b from-brand-50/60 to-white',
            )
        "
        role="status"
        aria-live="polite"
    >
        <template v-if="!failed">
            <span
                :class="
                    cn(
                        'relative flex size-16 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow-lg',
                        studio.gradient,
                    )
                "
            >
                <component :is="studio.icon" class="size-7" />
                <span
                    class="absolute -right-1.5 -bottom-1.5 flex size-6 items-center justify-center rounded-full bg-white shadow"
                >
                    <LoaderCircle class="size-4 animate-spin text-brand-600" />
                </span>
            </span>
            <div>
                <p class="font-sans text-base font-bold text-navy-950">
                    {{
                        request.status === 'queued'
                            ? 'Navbatda kutilmoqda…'
                            : `${studio.name} ishlamoqda…`
                    }}
                </p>
                <p class="mt-1 text-xs text-navy-500">
                    {{
                        request.chunksTotal > 1
                            ? `${request.chunksCompleted} / ${request.chunksTotal} bo'lak tayyor · `
                            : ''
                    }}Sahifani yopishingiz mumkin — natija "Tarix"da saqlanadi.
                </p>
            </div>
            <div
                class="h-2 w-full max-w-sm overflow-hidden rounded-full bg-brand-100"
            >
                <div
                    class="h-full rounded-full bg-gradient-to-r from-brand-500 to-brand-700 transition-[width] duration-700"
                    :style="{ width: `${percent}%` }"
                />
            </div>
        </template>

        <template v-else>
            <span
                class="flex size-14 items-center justify-center rounded-2xl bg-red-100 text-red-600"
            >
                <CircleAlert class="size-7" />
            </span>
            <div class="max-w-md">
                <p class="font-sans text-base font-bold text-navy-950">
                    So'rov bajarilmadi
                </p>
                <p class="mt-1 text-[13px] leading-relaxed text-navy-600">
                    {{ request.error ?? "Noma'lum xato." }}
                </p>
            </div>
            <button
                v-if="request.own"
                type="button"
                class="inline-flex h-9 items-center gap-2 rounded-lg border border-red-200 bg-white px-4 text-[13px] font-semibold text-red-700 transition-all hover:-translate-y-px hover:border-red-300 hover:shadow-sm"
                @click="$emit('retry')"
            >
                <RotateCcw class="size-4" /> Matnni formaga qaytarish
            </button>
        </template>
    </div>
</template>
