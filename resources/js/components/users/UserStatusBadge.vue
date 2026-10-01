<script setup lang="ts">
import { Ban, CircleCheck, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

/**
 * Holat: faol / bloklangan / o'chirilgan (rang + ikonka + matn).
 */
const props = defineProps<{
    isBlocked: boolean;
    isDeleted: boolean;
}>();

const state = computed(() => {
    if (props.isDeleted) {
        return {
            label: "O'chirilgan",
            icon: Trash2,
            class: 'bg-navy-50 text-navy-600 ring-navy-200',
        };
    }

    if (props.isBlocked) {
        return {
            label: 'Bloklangan',
            icon: Ban,
            class: 'bg-red-50 text-red-700 ring-red-200',
        };
    }

    return {
        label: 'Faol',
        icon: CircleCheck,
        class: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    };
});
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap ring-1 ring-inset',
                state.class,
            )
        "
    >
        <component :is="state.icon" class="size-3" />
        {{ state.label }}
    </span>
</template>
