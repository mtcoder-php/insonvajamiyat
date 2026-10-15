<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    ExternalLink,
    ImagePlus,
    LoaderCircle,
    PanelsTopLeft,
    Trash2,
    Upload,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import { formatFileSize } from '@/lib/format';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { IssueCommonCover } from '@/types';

/**
 * Admin → Jurnallar → «Umumiy muqova»: barcha sonlar uchun bitta muqova rasmi.
 * Son PDF ini yig'ishda rasm 1-betga qo'yiladi va ustiga son raqami, yili va
 * umumiy tartib raqami yoziladi (o'rni va rangi shu yerda tanlanadi).
 */
const props = defineProps<{ cover: IssueCommonCover; sample: string }>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({
    stamp: props.cover.stamp,
    position: props.cover.position,
    color: props.cover.color,
});

watch(open, (value) => {
    if (value) {
        form.defaults({
            stamp: props.cover.stamp,
            position: props.cover.position,
            color: props.cover.color,
        });
        form.reset();
        form.clearErrors();
    }
});

const uploading = ref(false);
const uploadError = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const positions = computed(() => [
    { value: 'bottom' as const, label: t('Pastda') },
    { value: 'top' as const, label: t('Tepada') },
]);

const colors = computed(() => [
    { value: 'gold' as const, label: t('Oltin'), swatch: 'bg-[#c9a24d]' },
    { value: 'white' as const, label: t('Oq'), swatch: 'bg-white' },
    { value: 'navy' as const, label: t("To'q ko'k"), swatch: 'bg-navy-950' },
]);

const stampColor = computed(
    () =>
        ({
            gold: 'text-[#d9b45f]',
            white: 'text-white',
            navy: 'text-navy-950',
        })[form.color],
);

function pick(): void {
    fileInput.value?.click();
}

function upload(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';

    if (!file) {
        return;
    }

    uploadError.value = null;
    uploading.value = true;

    router.post(
        props.cover.uploadUrl,
        { file },
        {
            forceFormData: true,
            preserveScroll: true,
            onError: (errors) => {
                uploadError.value = errors.file ?? t("Rasmni yuklab bo'lmadi.");
            },
            onFinish: () => {
                uploading.value = false;
            },
        },
    );
}

function remove(): void {
    router.delete(props.cover.deleteUrl, { preserveScroll: true });
}

function save(): void {
    form.put(props.cover.updateUrl, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <ActionDialog
        v-model:open="open"
        :icon="PanelsTopLeft"
        size="lg"
        :title="t('Umumiy muqova')"
        :description="
            t(
                'Barcha sonlar uchun bitta muqova. Son PDF ini yig\'ishda 1-betga qo\'yiladi va ustiga shu sonning raqami va yili yoziladi. Songa alohida muqova yuklansa — o\'shasi ishlatiladi.',
            )
        "
        :confirm-text="t('Saqlash')"
        :processing="form.processing"
        @confirm="save"
    >
        <div class="grid gap-5 sm:grid-cols-[11rem_minmax(0,1fr)]">
            <!-- Ko'rinish: muqova + son raqami (taxminiy joylashuv) -->
            <div
                class="group relative aspect-[1/1.414] overflow-hidden rounded-lg bg-navy-950 shadow-[0_14px_30px_-16px_rgba(0,30,60,0.6)] ring-1 ring-line"
            >
                <img
                    v-if="cover.url"
                    :src="cover.url"
                    :alt="t('Umumiy muqova')"
                    class="size-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                />
                <div
                    v-if="form.stamp && cover.custom"
                    :class="
                        cn(
                            'absolute inset-x-0 flex flex-col items-center px-2 py-3',
                            form.position === 'top'
                                ? 'top-0 bg-gradient-to-b'
                                : 'bottom-0 bg-gradient-to-t',
                            form.color === 'navy'
                                ? 'from-[#fcf9f3]/90 to-transparent'
                                : 'from-[#051428]/85 to-transparent',
                        )
                    "
                >
                    <span
                        :class="cn('font-serif text-sm font-bold', stampColor)"
                        >{{ sample }}</span
                    >
                </div>
                <span
                    v-if="!cover.custom"
                    class="absolute inset-x-2 bottom-2 rounded-md bg-white/90 px-2 py-1 text-center text-[10px] font-semibold text-navy-700"
                    >{{ t('Avtomatik dizayn') }}</span
                >
            </div>

            <div class="flex min-w-0 flex-col gap-4">
                <div>
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        class="hidden"
                        @change="upload"
                    />
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            :disabled="uploading"
                            class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-600 px-3.5 text-[13px] font-semibold text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500 disabled:opacity-60"
                            @click="pick"
                        >
                            <LoaderCircle
                                v-if="uploading"
                                class="size-4 animate-spin"
                            />
                            <Upload v-else-if="cover.custom" class="size-4" />
                            <ImagePlus v-else class="size-4" />
                            {{
                                cover.custom
                                    ? t('Almashtirish')
                                    : t('Muqova yuklash')
                            }}
                        </button>
                        <button
                            v-if="cover.custom"
                            type="button"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-line px-3 text-[13px] font-semibold text-red-600 transition-all hover:-translate-y-px hover:border-red-200 hover:bg-red-50"
                            @click="remove"
                        >
                            <Trash2 class="size-4" /> {{ t("O'chirish") }}
                        </button>
                        <a
                            :href="cover.previewUrl"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-line px-3 text-[13px] font-semibold text-navy-700 transition-all hover:-translate-y-px hover:border-brand-300 hover:text-brand-700"
                        >
                            <ExternalLink class="size-4" />
                            {{ t('Namuna (PDF)') }}
                        </a>
                    </div>
                    <p
                        v-if="cover.custom && cover.name"
                        class="mt-2 truncate text-xs text-navy-500"
                    >
                        {{ cover.name }}
                        <template v-if="cover.size">
                            · {{ formatFileSize(cover.size) }}</template
                        >
                    </p>
                    <p class="mt-1.5 text-[11px] leading-relaxed text-navy-400">
                        {{
                            t(
                                'PNG, JPG yoki WEBP, 20 MB gacha. Nisbati A4 (1 : 1,414), tavsiya — 2480 × 3508 px. Namuna «Namuna (PDF)» tugmasida — saqlangan sozlamalar bilan.',
                            )
                        }}
                    </p>
                    <p v-if="uploadError" class="mt-1.5 text-xs text-red-600">
                        {{ uploadError }}
                    </p>
                </div>

                <label
                    class="flex cursor-pointer items-start gap-3 rounded-lg border border-line p-3 transition-colors hover:border-brand-200 hover:bg-brand-50/40"
                >
                    <input
                        v-model="form.stamp"
                        type="checkbox"
                        class="mt-0.5 size-4 rounded border-navy-300 text-brand-600"
                    />
                    <span>
                        <span
                            class="block text-[13px] font-semibold text-navy-900"
                            >{{ t('Son raqami va yilni yozish') }}</span
                        >
                        <span class="block text-xs text-navy-500">{{
                            t(
                                "Muqovada raqam uchun bo'sh joy qoldirilgan bo'lsa yoqing. Raqam rasmning o'zida bo'lsa — o'chiring.",
                            )
                        }}</span>
                    </span>
                </label>

                <div
                    :class="
                        cn(
                            'grid gap-3 transition-opacity',
                            !form.stamp && 'pointer-events-none opacity-40',
                        )
                    "
                >
                    <div>
                        <p class="mb-1.5 text-xs font-semibold text-navy-600">
                            {{ t('Joylashuvi') }}
                        </p>
                        <div class="flex gap-1.5">
                            <button
                                v-for="item in positions"
                                :key="item.value"
                                type="button"
                                :class="
                                    cn(
                                        'h-8 flex-1 rounded-lg text-xs font-semibold transition-all',
                                        form.position === item.value
                                            ? 'bg-navy-900 text-white shadow-sm'
                                            : 'bg-white text-navy-600 ring-1 ring-line hover:-translate-y-px hover:ring-brand-200',
                                    )
                                "
                                @click="form.position = item.value"
                            >
                                {{ item.label }}
                            </button>
                        </div>
                    </div>
                    <div>
                        <p class="mb-1.5 text-xs font-semibold text-navy-600">
                            {{ t('Matn rangi') }}
                        </p>
                        <div class="flex gap-1.5">
                            <button
                                v-for="item in colors"
                                :key="item.value"
                                type="button"
                                :title="item.label"
                                :class="
                                    cn(
                                        'inline-flex h-8 flex-1 items-center justify-center gap-1.5 rounded-lg text-xs font-semibold transition-all',
                                        form.color === item.value
                                            ? 'bg-navy-900 text-white shadow-sm'
                                            : 'bg-white text-navy-600 ring-1 ring-line hover:-translate-y-px hover:ring-brand-200',
                                    )
                                "
                                @click="form.color = item.value"
                            >
                                <span
                                    :class="
                                        cn(
                                            'size-3 rounded-full ring-1 ring-navy-200',
                                            item.swatch,
                                        )
                                    "
                                />
                                {{ item.label }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ActionDialog>
</template>
