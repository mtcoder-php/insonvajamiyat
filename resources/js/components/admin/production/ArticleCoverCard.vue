<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ImagePlus, LoaderCircle, RefreshCw, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { cn } from '@/lib/utils';
import type { ProductionArticle } from '@/types';

/**
 * "Maqola rasmi": saytdagi katalog, maqola sahifasi va bosh sahifa kartalarida ko'rinadi.
 * Yuklash — bosish yoki sudrab tashlash; nashr etilgan maqolada ham almashtiriladi.
 */
const props = defineProps<{ article: ProductionArticle }>();

const input = ref<HTMLInputElement | null>(null);
const busy = ref(false);
const dragging = ref(false);
const error = ref<string | null>(null);

function upload(file: File | null | undefined): void {
    if (!file) {
        return;
    }

    error.value = null;
    busy.value = true;
    router.post(
        props.article.urls.cover,
        { cover: file },
        {
            preserveScroll: true,
            forceFormData: true,
            onError: (errors) => (error.value = errors.cover ?? null),
            onFinish: () => {
                busy.value = false;

                if (input.value) {
                    input.value.value = '';
                }
            },
        },
    );
}

function remove(): void {
    busy.value = true;
    router.delete(props.article.urls.cover, {
        preserveScroll: true,
        onFinish: () => (busy.value = false),
    });
}

function onDrop(event: DragEvent): void {
    dragging.value = false;
    upload(event.dataTransfer?.files[0]);
}
</script>

<template>
    <section
        class="rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-shadow duration-300 hover:shadow-[0_12px_32px_-18px_rgba(0,36,66,0.25)]"
    >
        <header class="mb-3 flex items-center justify-between gap-3">
            <h2 class="font-sans text-[15px] font-bold text-navy-950">
                Maqola rasmi
            </h2>
            <div
                v-if="article.coverUrl && article.can.cover"
                class="flex gap-1"
            >
                <button
                    type="button"
                    :disabled="busy"
                    class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700 disabled:opacity-50"
                    title="Almashtirish"
                    aria-label="Rasmni almashtirish"
                    @click="input?.click()"
                >
                    <RefreshCw class="size-4" />
                </button>
                <button
                    type="button"
                    :disabled="busy"
                    class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-red-50 hover:text-red-600 disabled:opacity-50"
                    title="O'chirish"
                    aria-label="Rasmni o'chirish"
                    @click="remove"
                >
                    <Trash2 class="size-4" />
                </button>
            </div>
        </header>

        <div
            v-if="article.coverUrl"
            class="group relative aspect-[3/2] overflow-hidden rounded-lg border border-line bg-[#eef2f8]"
        >
            <img
                :src="article.coverUrl"
                :alt="article.title"
                class="size-full object-cover transition-transform duration-500 group-hover:scale-105"
            />
            <div
                v-if="busy"
                class="absolute inset-0 flex items-center justify-center bg-white/70"
            >
                <LoaderCircle class="size-6 animate-spin text-brand-600" />
            </div>
        </div>

        <button
            v-else-if="article.can.cover"
            type="button"
            :disabled="busy"
            :class="
                cn(
                    'flex aspect-[3/2] w-full flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed px-4 text-center transition-all',
                    dragging
                        ? 'border-brand-500 bg-brand-50'
                        : 'border-line bg-[#fafcff] hover:border-brand-300 hover:bg-brand-50/40',
                )
            "
            @click="input?.click()"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
        >
            <LoaderCircle
                v-if="busy"
                class="size-7 animate-spin text-brand-600"
            />
            <ImagePlus v-else class="size-7 text-brand-600" />
            <span class="text-[13px] font-semibold text-navy-800"
                >Rasm tanlang yoki shu yerga tashlang</span
            >
            <span class="text-[11px] text-navy-500"
                >JPG, PNG yoki WEBP · kamida 600×400 px · 4 MB gacha</span
            >
        </button>
        <p v-else class="text-[13px] text-navy-500">Rasm yuklanmagan.</p>

        <p class="mt-2 text-[11px] text-navy-500">
            Saytdagi maqolalar katalogi, maqola sahifasi va bosh sahifada
            ko'rinadi. Tavsiya: 1200×800 px (3:2).
        </p>
        <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>

        <input
            ref="input"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="hidden"
            @change="upload(($event.target as HTMLInputElement).files?.[0])"
        />
    </section>
</template>
