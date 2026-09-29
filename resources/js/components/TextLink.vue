<script setup lang="ts">
import type { LinkComponentBaseProps, Method } from '@inertiajs/core';
import { Link } from '@inertiajs/vue3';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

/**
 * Matn ichidagi havola (dizayndagi ko'k havolalar).
 * Qo'shimcha klasslar tailwind-merge orqali birlashtiriladi.
 */
type Props = {
    href: LinkComponentBaseProps['href'];
    tabindex?: number;
    method?: Method;
    as?: string;
    class?: HTMLAttributes['class'];
};

const props = defineProps<Props>();
</script>

<template>
    <Link
        :href="href"
        :tabindex="tabindex"
        :method="method"
        :as="as"
        :class="
            cn(
                'font-medium text-brand-700 underline-offset-4 transition-colors hover:text-brand-800 hover:underline dark:text-brand-300',
                props.class,
            )
        "
    >
        <slot />
    </Link>
</template>
