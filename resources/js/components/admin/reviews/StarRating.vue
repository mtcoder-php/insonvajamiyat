<script setup lang="ts">
import { Star } from '@lucide/vue';
import { cn } from '@/lib/utils';
import { t } from '@/lib/i18n';

/**
 * Yulduzli baho (1–5, 0.5 qadam). Har bir yulduzning chap yarmi — x.5, o'ng yarmi — butun.
 */
const props = withDefaults(
    defineProps<{ readonly?: boolean; size?: string }>(),
    {
        readonly: false,
        size: 'size-7',
    },
);

const model = defineModel<number | null>({ default: null });

function fill(index: number): number {
    const value = model.value ?? 0;

    return Math.max(0, Math.min(1, value - (index - 1)));
}

function set(value: number): void {
    if (!props.readonly) {
        model.value = value;
    }
}
</script>

<template>
    <div
        class="flex items-center gap-1"
        role="radiogroup"
        :aria-label="t('Umumiy baho')"
    >
        <span
            v-for="index in 5"
            :key="index"
            :class="
                cn(
                    'relative inline-flex',
                    props.size,
                    !readonly && 'transition-transform hover:scale-110',
                )
            "
        >
            <Star
                :class="cn(props.size, 'text-navy-200')"
                :stroke-width="1.5"
            />
            <span
                class="absolute inset-y-0 left-0 overflow-hidden"
                :style="{ width: `${fill(index) * 100}%` }"
            >
                <Star
                    :class="cn(props.size, 'fill-amber-400 text-amber-400')"
                    :stroke-width="1.5"
                />
            </span>
            <template v-if="!readonly">
                <button
                    type="button"
                    class="absolute inset-y-0 left-0 w-1/2 cursor-pointer"
                    :aria-label="t(':score ball', { score: index - 0.5 })"
                    @click="set(index - 0.5)"
                />
                <button
                    type="button"
                    class="absolute inset-y-0 right-0 w-1/2 cursor-pointer"
                    :aria-label="t(':score ball', { score: index })"
                    @click="set(index)"
                />
            </template>
        </span>
    </div>
</template>
