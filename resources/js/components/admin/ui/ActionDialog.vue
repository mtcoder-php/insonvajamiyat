<script setup lang="ts">
import { LoaderCircle } from '@lucide/vue';
import type { Component } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    dangerButtonClass,
    primaryButtonClass,
    secondaryButtonClass,
} from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import { t } from '@/lib/i18n';

/**
 * Tasdiqlash oynasi: ikonka, sarlavha, izoh, qo'shimcha maydonlar (slot)
 * va "Bekor qilish" / tasdiqlash tugmalari.
 */
const props = withDefaults(
    defineProps<{
        title: string;
        description?: string;
        icon: Component;
        tone?: 'primary' | 'danger';
        confirmText: string;
        processing?: boolean;
        /** Tasdiqlash tugmasini o'chirib qo'yish (masalan, hali tanlanmagan) */
        disabled?: boolean;
        /** lg — keng forma (tarjimali maydonlar), xl — matn muharriri bor forma */
        size?: 'md' | 'lg' | 'xl';
    }>(),
    {
        description: undefined,
        tone: 'primary',
        processing: false,
        disabled: false,
        size: 'md',
    },
);

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{ confirm: [] }>();
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            :class="
                cn(
                    'gap-0 overflow-hidden border-line bg-white p-0 text-navy-900',
                    props.size === 'xl'
                        ? 'max-h-[94vh] overflow-y-auto sm:max-w-[min(68rem,calc(100vw-2rem))]'
                        : props.size === 'lg'
                          ? 'max-h-[92vh] overflow-y-auto sm:max-w-2xl'
                          : 'sm:max-w-md',
                )
            "
        >
            <form @submit.prevent="emit('confirm')">
                <div class="flex gap-4 p-6">
                    <span
                        :class="
                            cn(
                                'flex size-11 shrink-0 items-center justify-center rounded-full',
                                props.tone === 'danger'
                                    ? 'bg-red-50 text-red-600'
                                    : 'bg-brand-50 text-brand-600',
                            )
                        "
                    >
                        <component :is="icon" class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <DialogTitle
                            class="font-sans text-base font-bold text-navy-950"
                        >
                            {{ title }}
                        </DialogTitle>
                        <DialogDescription
                            v-if="description"
                            class="mt-1 text-sm leading-relaxed text-navy-600"
                        >
                            {{ description }}
                        </DialogDescription>
                        <div v-if="$slots.default" class="mt-4">
                            <slot />
                        </div>
                    </div>
                </div>
                <div
                    class="sticky bottom-0 z-10 flex justify-end gap-2 border-t border-line bg-[#f8fafc] px-6 py-3"
                >
                    <button
                        type="button"
                        :class="cn(secondaryButtonClass, 'h-9')"
                        @click="open = false"
                    >
                        {{ t('Bekor qilish') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="processing || disabled"
                        :class="
                            cn(
                                props.tone === 'danger'
                                    ? dangerButtonClass
                                    : primaryButtonClass,
                                'h-9',
                            )
                        "
                    >
                        <LoaderCircle
                            v-if="processing"
                            class="size-4 animate-spin"
                        />
                        {{ confirmText }}
                    </button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
