<script setup lang="ts">
import { ImagePlus, LoaderCircle, Trash2, Undo2 } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { prepareImage } from '@/lib/image';
import { cn } from '@/lib/utils';

/**
 * Rasm tanlash maydoni (yangilik, tadbir, kitob muqovasi, hamkor logosi):
 * oldindan ko'rish, almashtirish va olib tashlash. Katta rasm brauzerda
 * kichraytiriladi (`prepare`); logolar uchun `prepare = null` (shaffoflik saqlanadi).
 */
const props = withDefaults(
    defineProps<{
        currentUrl: string | null;
        hint: string;
        aspectClass?: string;
        fit?: 'cover' | 'contain';
        error?: string;
        prepare?: { maxSide: number; maxBytes: number } | null;
        removable?: boolean;
    }>(),
    {
        aspectClass: 'aspect-[16/8]',
        fit: 'cover',
        error: undefined,
        prepare: () => ({ maxSide: 1920, maxBytes: 1_800_000 }),
        removable: true,
    },
);

const file = defineModel<File | null>('file', { default: null });
const removed = defineModel<boolean>('removed', { default: false });

const input = ref<HTMLInputElement | null>(null);
const blobUrl = ref<string | null>(null);
const preparing = ref(false);

function revoke(): void {
    if (blobUrl.value) {
        URL.revokeObjectURL(blobUrl.value);
        blobUrl.value = null;
    }
}

// Forma qayta ochilganda (file = null) eski ko'rinish tozalanadi
watch(file, (value) => {
    if (!value) {
        revoke();
    }
});

onBeforeUnmount(revoke);

const preview = computed(
    () => blobUrl.value ?? (removed.value ? null : props.currentUrl),
);

async function pick(event: Event): Promise<void> {
    const target = event.target as HTMLInputElement;
    const picked = target.files?.[0];
    target.value = '';

    if (!picked) {
        return;
    }

    preparing.value = true;
    const prepared = props.prepare
        ? await prepareImage(picked, props.prepare)
        : picked;
    preparing.value = false;

    revoke();
    file.value = prepared;
    removed.value = false;
    blobUrl.value = URL.createObjectURL(prepared);
}

function clear(): void {
    revoke();
    file.value = null;
    removed.value = !!props.currentUrl;
}
</script>

<template>
    <div class="grid gap-1.5">
        <div class="relative">
            <button
                type="button"
                :class="
                    cn(
                        'group relative flex w-full items-center justify-center overflow-hidden rounded-xl border-2 border-dashed transition-colors',
                        aspectClass,
                        error
                            ? 'border-red-300 bg-red-50/40'
                            : 'border-line bg-[#fafcff] hover:border-brand-300',
                    )
                "
                @click="input?.click()"
            >
                <img
                    v-if="preview"
                    :src="preview"
                    alt=""
                    :class="
                        cn(
                            'absolute inset-0 size-full transition-transform duration-500 group-hover:scale-105',
                            fit === 'contain'
                                ? 'object-contain p-4'
                                : 'object-cover',
                        )
                    "
                />
                <span
                    :class="
                        cn(
                            'relative flex flex-col items-center gap-1.5 rounded-lg px-4 py-3 text-center text-xs',
                            preview
                                ? 'bg-navy-950/60 text-white opacity-0 backdrop-blur-sm transition-opacity group-hover:opacity-100'
                                : 'text-navy-500',
                        )
                    "
                >
                    <LoaderCircle
                        v-if="preparing"
                        class="size-6 animate-spin"
                    />
                    <ImagePlus v-else class="size-6" />
                    <span class="font-semibold">{{
                        preview ? 'Rasmni almashtirish' : 'Rasm tanlang'
                    }}</span>
                    <span>{{ hint }}</span>
                </span>
            </button>
            <button
                v-if="removable && preview"
                type="button"
                class="absolute top-2 right-2 inline-flex size-8 items-center justify-center rounded-lg bg-white/95 text-navy-600 shadow-sm ring-1 ring-black/5 transition-all hover:scale-105 hover:bg-red-50 hover:text-red-600"
                aria-label="Rasmni olib tashlash"
                @click.stop="clear"
            >
                <Trash2 class="size-4" />
            </button>
            <button
                v-if="removed && !preview && currentUrl"
                type="button"
                class="absolute top-2 right-2 inline-flex h-8 items-center gap-1.5 rounded-lg bg-white px-2.5 text-xs font-semibold text-navy-700 shadow-sm ring-1 ring-black/5 transition-colors hover:text-brand-700"
                @click.stop="removed = false"
            >
                <Undo2 class="size-3.5" /> Qaytarish
            </button>
        </div>
        <p v-if="error" class="text-xs font-medium text-red-600">
            {{ error }}
        </p>
        <input
            ref="input"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="hidden"
            @change="pick"
        />
    </div>
</template>
