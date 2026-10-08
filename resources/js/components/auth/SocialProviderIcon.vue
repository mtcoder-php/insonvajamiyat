<script setup lang="ts">
import { Globe, IdCard } from '@lucide/vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { SocialProviderOption } from '@/types';

/**
 * Provayder belgisi: public/images/social/{key}.svg bo'lsa — o'sha rasm,
 * aks holda provayder rangidagi umumiy ikonka.
 */
const props = defineProps<{
    provider: Pick<SocialProviderOption, 'key' | 'icon'>;
    class?: string;
}>();

const fallback = computed(() =>
    props.provider.key === 'orcid'
        ? { icon: IdCard, tone: 'bg-lime-50 text-lime-700 ring-lime-200' }
        : { icon: Globe, tone: 'bg-brand-50 text-brand-600 ring-brand-200' },
);
</script>

<template>
    <img
        v-if="provider.icon"
        :src="provider.icon"
        alt=""
        aria-hidden="true"
        :class="cn('size-5 shrink-0 object-contain', props.class)"
    />
    <span
        v-else
        aria-hidden="true"
        :class="
            cn(
                'grid size-6 shrink-0 place-items-center rounded-full ring-1',
                fallback.tone,
                props.class,
            )
        "
    >
        <component :is="fallback.icon" class="size-3.5" />
    </span>
</template>
