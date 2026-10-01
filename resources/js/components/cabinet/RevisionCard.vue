<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    FilePenLine,
    FileUp,
    LoaderCircle,
    Paperclip,
    Send,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import { primaryButtonClass, textareaClass } from '@/lib/formStyles';
import { formatDate, formatFileSize } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { RevisionRequest } from '@/types';

/**
 * "Tuzatish talab etiladi": muharrir izohi va tuzatilgan versiyani yuborish formasi.
 */
const props = defineProps<{ revision: RevisionRequest; hasReviews: boolean }>();

const MIN_RESPONSE = 20;
const MAX_SUPPLEMENTARY = 5;

const form = useForm<{
    manuscript: File | null;
    response: string;
    supplementary: File[];
}>({
    manuscript: null,
    response: '',
    supplementary: [],
});

const errors = computed(() => form.errors as Record<string, string>);
const supplementaryError = computed(
    () =>
        errors.value.supplementary ??
        Object.entries(errors.value).find(([key]) =>
            key.startsWith('supplementary.'),
        )?.[1],
);

const manuscriptInput = ref<HTMLInputElement | null>(null);
const supplementaryInput = ref<HTMLInputElement | null>(null);
const dragging = ref(false);
const confirmOpen = ref(false);

const ready = computed(
    () =>
        form.manuscript !== null && form.response.trim().length >= MIN_RESPONSE,
);

function setManuscript(file: File | null | undefined): void {
    if (file) {
        form.manuscript = file;
        form.clearErrors('manuscript');
    }
}

function onDrop(event: DragEvent): void {
    dragging.value = false;
    setManuscript(event.dataTransfer?.files[0]);
}

function addSupplementary(event: Event): void {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);
    form.supplementary = [...form.supplementary, ...files].slice(
        0,
        MAX_SUPPLEMENTARY,
    );
    input.value = '';
}

function removeSupplementary(index: number): void {
    form.supplementary = form.supplementary.filter((_, i) => i !== index);
}

function submit(): void {
    form.post(props.revision.url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => (confirmOpen.value = false),
        onError: () => (confirmOpen.value = false),
    });
}
</script>

<template>
    <section
        id="revision"
        class="overflow-hidden rounded-xl border border-orange-200 bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-shadow duration-300 hover:shadow-[0_14px_34px_-20px_rgba(234,88,12,0.45)]"
    >
        <header
            class="flex items-start gap-3 border-b border-orange-100 bg-gradient-to-r from-orange-50 to-amber-50/40 px-5 py-4"
        >
            <span
                class="flex size-10 shrink-0 items-center justify-center rounded-full bg-orange-500 text-white shadow-[0_8px_18px_-8px_rgba(234,88,12,0.9)]"
            >
                <FilePenLine class="size-5" />
            </span>
            <div class="min-w-0">
                <h2 class="font-sans text-[15px] font-bold text-navy-950">
                    Maqolani tuzatish talab etiladi
                </h2>
                <p class="mt-0.5 text-xs text-navy-600">
                    <template v-if="revision.requestedAt">
                        {{ formatDate(revision.requestedAt) }} ·
                    </template>
                    <template v-if="revision.round > 0">
                        {{ revision.round }}-taqriz raundi natijasi.
                    </template>
                    Izohlarni inobatga olib, tuzatilgan faylni yuboring.
                </p>
            </div>
        </header>

        <div class="grid gap-5 p-5">
            <div v-if="revision.comment">
                <p
                    class="mb-1.5 text-[11px] font-semibold tracking-wide text-orange-700 uppercase"
                >
                    Muharrir izohi
                </p>
                <p
                    class="rounded-lg border-l-4 border-orange-400 bg-orange-50/60 px-4 py-3 text-[13px] leading-relaxed whitespace-pre-line text-navy-800"
                >
                    {{ revision.comment }}
                </p>
                <a
                    v-if="hasReviews"
                    href="#reviews"
                    class="mt-2 inline-block text-xs font-semibold text-brand-700 hover:underline"
                >
                    Taqrizchilar izohlarini ko'rish ↓
                </a>
            </div>

            <form class="grid gap-4" @submit.prevent="confirmOpen = true">
                <div>
                    <p class="mb-1.5 text-xs font-semibold text-navy-800">
                        Tuzatilgan fayl <span class="text-red-500">*</span>
                    </p>
                    <button
                        type="button"
                        :class="
                            cn(
                                'flex w-full flex-col items-center gap-2 rounded-xl border-2 border-dashed px-4 py-6 text-center transition-all',
                                dragging
                                    ? 'border-brand-400 bg-brand-50'
                                    : form.manuscript
                                      ? 'border-emerald-300 bg-emerald-50/50'
                                      : 'border-navy-200 hover:border-brand-300 hover:bg-brand-50/40',
                                errors.manuscript && 'border-red-300',
                            )
                        "
                        @click="manuscriptInput?.click()"
                        @dragover.prevent="dragging = true"
                        @dragleave.prevent="dragging = false"
                        @drop.prevent="onDrop"
                    >
                        <FileUp
                            :class="
                                cn(
                                    'size-7',
                                    form.manuscript
                                        ? 'text-emerald-600'
                                        : 'text-brand-500',
                                )
                            "
                        />
                        <span
                            v-if="form.manuscript"
                            class="text-[13px] font-semibold text-navy-900"
                        >
                            {{ form.manuscript.name }}
                            <span class="font-normal text-navy-500">
                                · {{ formatFileSize(form.manuscript.size) }}
                            </span>
                        </span>
                        <span v-else class="text-[13px] text-navy-700">
                            <b class="text-brand-700">Faylni tanlang</b> yoki
                            shu yerga tashlang
                        </span>
                        <span class="text-[11px] text-navy-500">
                            .docx yoki .pdf, 10 MB gacha
                        </span>
                    </button>
                    <input
                        ref="manuscriptInput"
                        type="file"
                        accept=".docx,.pdf"
                        class="hidden"
                        @change="
                            setManuscript(
                                ($event.target as HTMLInputElement).files?.[0],
                            )
                        "
                    />
                    <p
                        v-if="errors.manuscript"
                        class="mt-1 text-xs text-red-600"
                    >
                        {{ errors.manuscript }}
                    </p>
                </div>

                <label class="block">
                    <span
                        class="mb-1.5 block text-xs font-semibold text-navy-800"
                    >
                        Taqrizchi va muharrirga javob
                        <span class="text-red-500">*</span>
                    </span>
                    <textarea
                        v-model="form.response"
                        rows="5"
                        maxlength="5000"
                        placeholder="Har bir izoh bo'yicha nimalar o'zgartirilganini qisqacha yozing (masalan: «1-izoh: metodologiya bo'limi kengaytirildi, 4-bet»)."
                        :aria-invalid="!!errors.response"
                        :class="textareaClass"
                    />
                    <span class="mt-1 flex justify-between text-[11px]">
                        <span class="text-red-600">{{ errors.response }}</span>
                        <span
                            :class="
                                form.response.trim().length < MIN_RESPONSE
                                    ? 'text-amber-600'
                                    : 'text-navy-400'
                            "
                        >
                            {{ form.response.length }}/5000
                        </span>
                    </span>
                </label>

                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            :disabled="
                                form.supplementary.length >= MAX_SUPPLEMENTARY
                            "
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-line px-3 text-xs font-semibold text-navy-700 transition-all hover:-translate-y-px hover:border-brand-300 hover:text-brand-700 disabled:opacity-50"
                            @click="supplementaryInput?.click()"
                        >
                            <Paperclip class="size-4" /> Ilova qo'shish ({{
                                form.supplementary.length
                            }}/{{ MAX_SUPPLEMENTARY }})
                        </button>
                        <span
                            v-for="(file, i) in form.supplementary"
                            :key="`${file.name}-${i}`"
                            class="inline-flex max-w-56 items-center gap-1 rounded-lg bg-brand-50 px-2 py-1 text-xs font-medium text-brand-800"
                        >
                            <span class="truncate">{{ file.name }}</span>
                            <button
                                type="button"
                                class="rounded p-0.5 hover:bg-brand-100"
                                aria-label="Olib tashlash"
                                @click="removeSupplementary(i)"
                            >
                                <X class="size-3.5" />
                            </button>
                        </span>
                    </div>
                    <input
                        ref="supplementaryInput"
                        type="file"
                        multiple
                        accept=".docx,.pdf,.xlsx,.png,.jpg,.jpeg,.zip"
                        class="hidden"
                        @change="addSupplementary"
                    />
                    <p
                        v-if="supplementaryError"
                        class="mt-1 text-xs text-red-600"
                    >
                        {{ supplementaryError }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3">
                    <p v-if="!ready" class="text-[11px] text-navy-500">
                        Fayl va kamida {{ MIN_RESPONSE }} belgili javob kerak.
                    </p>
                    <button
                        type="submit"
                        :class="primaryButtonClass"
                        :disabled="!ready || form.processing"
                    >
                        <Send class="size-4" />
                        Tuzatilgan versiyani yuborish
                    </button>
                </div>
            </form>
        </div>

        <ActionDialog
            v-model:open="confirmOpen"
            title="Tuzatilgan versiyani yuborasizmi?"
            description="Yuborilgandan so'ng maqola tahririyatda qayta ko'rib chiqiladi va uni o'zgartirib bo'lmaydi."
            :icon="form.processing ? LoaderCircle : Send"
            confirm-text="Ha, yuborish"
            :processing="form.processing"
            @confirm="submit"
        />
    </section>
</template>
