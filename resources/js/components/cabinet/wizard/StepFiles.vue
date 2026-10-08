<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Download, FileText, Paperclip, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import FileDropzone from '@/components/cabinet/wizard/FileDropzone.vue';
import WizardFooter from '@/components/cabinet/wizard/WizardFooter.vue';
import { formatFileSize } from '@/lib/format';
import type { ArticleDraft, DraftFile, WizardLimits } from '@/types';
import { t } from '@/lib/i18n';

/**
 * 5-bosqich: asosiy fayl (bitta, yangisi eskisini almashtiradi) va qo'shimcha fayllar.
 * Fayl tanlanishi bilan serverga yuklanadi.
 */
const props = defineProps<{
    article: ArticleDraft;
    limits: WizardLimits;
    prevHref: string | null;
    nextHref: string | null;
}>();

type UploadType = 'manuscript' | 'supplementary';

const form = useForm<{ type: UploadType; file: File | null }>({
    type: 'manuscript',
    file: null,
});

const manuscript = computed(() =>
    props.article.files.find((file) => file.type === 'manuscript'),
);
const supplementary = computed(() =>
    props.article.files.filter((file) => file.type === 'supplementary'),
);

function upload(type: UploadType, file: File): void {
    form.type = type;
    form.file = file;
    form.post(props.article.urls.files, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

const deleting = ref<string | null>(null);

function remove(file: DraftFile): void {
    deleting.value = file.uuid;
    router.delete(file.deleteUrl, {
        preserveScroll: true,
        onFinish: () => (deleting.value = null),
    });
}

const uploadingType = computed(() => (form.processing ? form.type : null));
const errorFor = (type: UploadType): string | undefined =>
    form.type === type ? form.errors.file : undefined;

function next(): void {
    if (props.nextHref) {
        router.visit(props.nextHref);
    }
}
</script>

<template>
    <div class="grid gap-7">
        <section class="grid gap-3">
            <header>
                <h3
                    class="flex items-center gap-2 text-sm font-bold text-navy-950"
                >
                    <FileText class="size-4 text-brand-600" />
                    {{ t('Maqolaning asosiy fayli') }}
                    <span class="text-red-500">*</span>
                </h3>
                <p class="mt-0.5 text-xs text-navy-500">
                    {{
                        t(
                            "Jurnal shabloni asosida tayyorlangan to'liq matn. Yangi fayl yuklasangiz, avvalgisi almashtiriladi.",
                        )
                    }}
                </p>
            </header>

            <article
                v-if="manuscript"
                class="group flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50/40 p-3.5 transition-all hover:shadow-[0_10px_24px_-18px_rgba(5,150,105,0.8)]"
            >
                <span
                    class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-white text-[11px] font-bold text-emerald-700 uppercase ring-1 ring-emerald-200"
                >
                    {{ manuscript.extension }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-navy-900">
                        {{ manuscript.name }}
                    </p>
                    <p class="text-xs text-navy-500">
                        {{ formatFileSize(manuscript.size) }}
                    </p>
                </div>
                <a
                    :href="manuscript.url"
                    class="flex size-9 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-white hover:text-brand-700"
                    :aria-label="t('Yuklab olish')"
                >
                    <Download class="size-4" />
                </a>
            </article>

            <FileDropzone
                :limit="limits.files.manuscript"
                :title="
                    manuscript
                        ? t('Boshqa fayl bilan almashtirish')
                        : t('Asosiy faylni yuklang')
                "
                :uploading="uploadingType === 'manuscript'"
                :progress="form.progress?.percentage ?? null"
                :disabled="form.processing"
                :error="errorFor('manuscript')"
                @select="upload('manuscript', $event)"
            />
        </section>

        <section class="grid gap-3">
            <header class="flex items-end justify-between gap-3">
                <div>
                    <h3
                        class="flex items-center gap-2 text-sm font-bold text-navy-950"
                    >
                        <Paperclip class="size-4 text-brand-600" />
                        {{ t("Qo'shimcha fayllar") }}
                    </h3>
                    <p class="mt-0.5 text-xs text-navy-500">
                        {{ t('Ixtiyoriy: rasmlar, jadvallar, ilovalar.') }}
                    </p>
                </div>
                <span class="text-xs text-navy-400 tabular-nums">
                    {{ supplementary.length }} / {{ limits.supplementaryMax }}
                </span>
            </header>

            <ul v-if="supplementary.length" class="grid gap-2 sm:grid-cols-2">
                <li
                    v-for="file in supplementary"
                    :key="file.uuid"
                    class="group flex items-center gap-3 rounded-lg border border-line bg-white p-2.5 transition-all hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-[0_10px_20px_-16px_rgba(0,36,66,0.5)]"
                >
                    <span
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-[10px] font-bold text-brand-700 uppercase"
                    >
                        {{ file.extension }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <a
                            :href="file.url"
                            class="block truncate text-[13px] font-medium text-navy-900 hover:text-brand-700"
                        >
                            {{ file.name }}
                        </a>
                        <p class="text-[11px] text-navy-500">
                            {{ formatFileSize(file.size) }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="flex size-8 items-center justify-center rounded-lg text-navy-400 transition-colors hover:bg-red-50 hover:text-red-600 disabled:opacity-40"
                        :disabled="deleting === file.uuid"
                        :aria-label="
                            t(':name — o\'chirish', { name: file.name })
                        "
                        @click="remove(file)"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </li>
            </ul>

            <FileDropzone
                v-if="supplementary.length < limits.supplementaryMax"
                :limit="limits.files.supplementary"
                :title="t('Qo\'shimcha fayl qo\'shish')"
                :uploading="uploadingType === 'supplementary'"
                :progress="form.progress?.percentage ?? null"
                :disabled="form.processing"
                :error="errorFor('supplementary')"
                @select="upload('supplementary', $event)"
            />
        </section>

        <WizardFooter
            class="mt-0"
            :prev-href="prevHref"
            :show-draft="false"
            :processing="form.processing"
            next-label="Davom etish"
            @next="next"
        />
    </div>
</template>
