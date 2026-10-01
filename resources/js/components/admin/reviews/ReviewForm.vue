<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    CircleCheck,
    CircleX,
    Download,
    FileUp,
    LoaderCircle,
    Lock,
    Save,
    Send,
    ShieldCheck,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import StarRating from '@/components/admin/reviews/StarRating.vue';
import {
    primaryButtonClass,
    secondaryButtonClass,
    textareaClass,
} from '@/lib/formStyles';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ReviewDetail, ReviewOptions } from '@/types';

/**
 * Taqrizlash formasi (o'ng ustun): taklifga javob, baholash, tavsiya va izohlar.
 */
const props = defineProps<{ review: ReviewDetail; options: ReviewOptions }>();

const MIN_AUTHOR = 50;

const form = useForm<{
    score: number | null;
    criteria: Record<string, number | null>;
    recommendation: string | null;
    comments_to_author: string;
    comments_to_editor: string;
    attachment: File | null;
}>({
    score: props.review.form.score,
    criteria: Object.fromEntries(
        props.options.criteria.map((c) => [
            c.key,
            props.review.form.criteria[c.key] ?? null,
        ]),
    ),
    recommendation: props.review.form.recommendation,
    comments_to_author: props.review.form.comments_to_author ?? '',
    comments_to_editor: props.review.form.comments_to_editor ?? '',
    attachment: null,
});

// "submit" — useForm uchun band nom, shuning uchun alohida ref.
const submitting = ref(false);

const errors = computed(() => form.errors as Record<string, string>);

const average = computed(() => {
    const values = Object.values(form.criteria).filter(
        (v): v is number => v !== null,
    );

    return values.length
        ? Math.round((values.reduce((a, b) => a + b, 0) / values.length) * 2) /
              2
        : null;
});

const shownScore = computed(() => form.score ?? average.value);

const ready = computed(
    () =>
        Object.values(form.criteria).every((v) => v !== null) &&
        form.recommendation !== null &&
        form.comments_to_author.trim().length >= MIN_AUTHOR,
);

const recommendationTint: Record<string, string> = {
    accept: 'peer-checked:border-emerald-400 peer-checked:bg-emerald-50',
    minor_revision: 'peer-checked:border-sky-400 peer-checked:bg-sky-50',
    major_revision: 'peer-checked:border-amber-400 peer-checked:bg-amber-50',
    reject: 'peer-checked:border-red-400 peer-checked:bg-red-50',
};

const fileInput = ref<HTMLInputElement | null>(null);

function pickFile(event: Event): void {
    const target = event.target as HTMLInputElement;
    form.attachment = target.files?.[0] ?? null;
}

function save(submit: boolean): void {
    submitting.value = submit;
    form.transform((data) => ({
        ...data,
        submit: submit ? 1 : 0,
        _method: 'put',
    })).post(props.review.urls.save, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.attachment = null;

            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
}

// Taklifga javob
const accepting = ref(false);
const declineOpen = ref(false);
const declineForm = useForm({ reason: '' });

function accept(): void {
    accepting.value = true;
    router.post(
        props.review.urls.accept,
        {},
        {
            preserveScroll: true,
            onFinish: () => (accepting.value = false),
        },
    );
}

function decline(): void {
    declineForm.post(props.review.urls.decline, {
        onSuccess: () => (declineOpen.value = false),
    });
}

const recommendationLabel = computed(
    () =>
        props.options.recommendations.find(
            (r) => r.value === props.review.form.recommendation,
        )?.label ?? '—',
);
</script>

<template>
    <aside
        class="rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)] xl:sticky xl:top-4"
    >
        <header class="border-b border-line px-5 py-4">
            <h2 class="font-sans text-base font-bold text-navy-950">
                Taqrizlash formasi
            </h2>
        </header>

        <div class="grid gap-5 p-5">
            <div
                class="flex gap-3 rounded-xl border border-brand-100 bg-brand-50/70 p-3.5"
            >
                <span
                    class="flex size-9 shrink-0 items-center justify-center rounded-full bg-white text-brand-600 shadow-sm"
                >
                    <ShieldCheck class="size-[18px]" />
                </span>
                <div class="text-xs leading-relaxed text-navy-600">
                    <p class="text-[13px] font-semibold text-navy-900">
                        Blind Review
                    </p>
                    Taqrizingizni faqat muharrir ko'radi. Muallif esa sizning
                    shaxsingizni bilmaydi.
                </div>
            </div>

            <!-- 1. Taklifga javob -->
            <template v-if="review.can.respond">
                <div class="grid gap-3 text-sm leading-relaxed text-navy-700">
                    <p>
                        Sizni ushbu maqolaga taqrizchi sifatida taklif qilishdi.
                        Taklifni qabul qilsangiz, maqola fayllari ochiladi va
                        taqriz formasi faollashadi.
                    </p>
                    <p
                        v-if="review.dueAt"
                        class="rounded-lg bg-[#f5f8fc] px-3 py-2 text-xs text-navy-600"
                    >
                        Taqriz muddati:
                        <b class="text-navy-900">{{
                            formatDate(review.dueAt)
                        }}</b>
                    </p>
                </div>
                <div class="grid gap-2">
                    <button
                        type="button"
                        :class="primaryButtonClass"
                        :disabled="accepting"
                        @click="accept"
                    >
                        <LoaderCircle
                            v-if="accepting"
                            class="size-4 animate-spin"
                        />
                        <CircleCheck v-else class="size-4" />
                        Taklifni qabul qilish
                    </button>
                    <button
                        type="button"
                        :class="
                            cn(
                                secondaryButtonClass,
                                'hover:border-red-300 hover:text-red-700',
                            )
                        "
                        @click="declineOpen = true"
                    >
                        <CircleX class="size-4" /> Rad etish
                    </button>
                </div>
            </template>

            <!-- 2. Taqriz formasi -->
            <form
                v-else-if="review.can.edit"
                class="grid gap-5"
                @submit.prevent="save(true)"
            >
                <p
                    v-if="errors.review"
                    class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
                >
                    {{ errors.review }}
                </p>

                <div>
                    <h3 class="mb-2 text-sm font-bold text-navy-900">
                        1. Umumiy baho
                    </h3>
                    <div class="flex items-center justify-between gap-3">
                        <StarRating v-model="form.score" />
                        <span
                            class="rounded-lg bg-[#f5f8fc] px-2.5 py-1 text-sm font-bold text-navy-900 tabular-nums"
                        >
                            {{ shownScore?.toFixed(1) ?? '—' }}
                            <span class="font-medium text-navy-400">/ 5</span>
                        </span>
                    </div>
                    <p class="mt-1 text-[11px] text-navy-500">
                        Tanlanmasa, mezonlar o'rtachasi olinadi.
                    </p>
                    <p v-if="errors.score" class="mt-1 text-xs text-red-600">
                        {{ errors.score }}
                    </p>
                </div>

                <div>
                    <h3 class="mb-3 text-sm font-bold text-navy-900">
                        2. Baholash mezonlari
                    </h3>
                    <div class="grid gap-3.5">
                        <label
                            v-for="criterion in options.criteria"
                            :key="criterion.key"
                            class="grid grid-cols-[1fr_9rem_2.75rem] items-center gap-3"
                        >
                            <span class="text-[13px] font-medium text-navy-800">
                                {{ criterion.label }}
                            </span>
                            <input
                                type="range"
                                min="1"
                                max="5"
                                step="0.5"
                                :value="form.criteria[criterion.key] ?? 3"
                                :class="
                                    cn(
                                        'h-1.5 w-full cursor-pointer accent-brand-600',
                                        form.criteria[criterion.key] === null &&
                                            'opacity-40',
                                    )
                                "
                                @input="
                                    form.criteria[criterion.key] = Number(
                                        ($event.target as HTMLInputElement)
                                            .value,
                                    )
                                "
                            />
                            <span
                                :class="
                                    cn(
                                        'rounded-md border px-1.5 py-0.5 text-center text-xs font-bold tabular-nums',
                                        errors[`criteria.${criterion.key}`]
                                            ? 'border-red-300 text-red-600'
                                            : 'border-line text-navy-800',
                                    )
                                "
                            >
                                {{
                                    form.criteria[criterion.key]?.toFixed(1) ??
                                    '—'
                                }}
                            </span>
                        </label>
                    </div>
                </div>

                <div>
                    <h3 class="mb-2 text-sm font-bold text-navy-900">
                        3. Taqriz va izohlar
                    </h3>
                    <textarea
                        v-model="form.comments_to_author"
                        rows="6"
                        maxlength="5000"
                        placeholder="Taqrizingizni yozing: maqolaning kuchli va zaif tomonlari, tavsiyalar... (muallifga anonim ko'rsatiladi)"
                        :aria-invalid="!!errors.comments_to_author"
                        :class="textareaClass"
                    />
                    <div class="mt-1 flex justify-between text-[11px]">
                        <span class="text-red-600">{{
                            errors.comments_to_author
                        }}</span>
                        <span
                            :class="
                                form.comments_to_author.trim().length <
                                MIN_AUTHOR
                                    ? 'text-amber-600'
                                    : 'text-navy-400'
                            "
                        >
                            {{ form.comments_to_author.length }}/5000
                        </span>
                    </div>
                    <label class="mt-3 block">
                        <span
                            class="mb-1 block text-xs font-semibold text-navy-700"
                        >
                            Muharrir uchun maxfiy izoh
                        </span>
                        <textarea
                            v-model="form.comments_to_editor"
                            rows="3"
                            maxlength="2000"
                            placeholder="Faqat muharrir ko'radi..."
                            :class="cn(textareaClass, 'min-h-20')"
                        />
                    </label>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            :class="cn(secondaryButtonClass, 'h-9 text-xs')"
                            @click="fileInput?.click()"
                        >
                            <FileUp class="size-4" />
                            {{
                                form.attachment
                                    ? form.attachment.name
                                    : 'Taqriz faylini biriktirish'
                            }}
                        </button>
                        <a
                            v-if="review.form.attachmentUrl && !form.attachment"
                            :href="review.form.attachmentUrl"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:underline"
                        >
                            <Download class="size-3.5" /> Yuklangan fayl
                        </a>
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".pdf,.doc,.docx"
                            class="hidden"
                            @change="pickFile"
                        />
                    </div>
                    <p
                        v-if="errors.attachment"
                        class="mt-1 text-xs text-red-600"
                    >
                        {{ errors.attachment }}
                    </p>
                </div>

                <div>
                    <h3 class="mb-2 text-sm font-bold text-navy-900">
                        4. Qaror
                    </h3>
                    <div class="grid gap-2" role="radiogroup">
                        <label
                            v-for="option in options.recommendations"
                            :key="option.value"
                            class="cursor-pointer"
                        >
                            <input
                                v-model="form.recommendation"
                                type="radio"
                                name="recommendation"
                                :value="option.value"
                                class="peer sr-only"
                            />
                            <span
                                :class="
                                    cn(
                                        'flex items-center gap-2.5 rounded-lg border border-line px-3 py-2 text-[13px] font-medium text-navy-800 transition-colors peer-focus-visible:ring-4 peer-focus-visible:ring-brand-100 hover:border-brand-200',
                                        recommendationTint[option.value],
                                    )
                                "
                            >
                                <span
                                    :class="
                                        cn(
                                            'flex size-4 items-center justify-center rounded-full border-2',
                                            form.recommendation === option.value
                                                ? 'border-brand-600'
                                                : 'border-navy-200',
                                        )
                                    "
                                >
                                    <span
                                        v-if="
                                            form.recommendation === option.value
                                        "
                                        class="size-2 rounded-full bg-brand-600"
                                    />
                                </span>
                                {{ option.label }}
                            </span>
                        </label>
                    </div>
                    <p
                        v-if="errors.recommendation"
                        class="mt-1 text-xs text-red-600"
                    >
                        {{ errors.recommendation }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <button
                        type="submit"
                        :class="cn(primaryButtonClass, 'h-11')"
                        :disabled="form.processing || !ready"
                    >
                        <LoaderCircle
                            v-if="form.processing && submitting"
                            class="size-4 animate-spin"
                        />
                        <Send v-else class="size-4" />
                        Taqrizni yuborish
                    </button>
                    <button
                        type="button"
                        :class="secondaryButtonClass"
                        :disabled="form.processing"
                        @click="save(false)"
                    >
                        <Save class="size-4" /> Qoralama saqlash
                    </button>
                    <p
                        v-if="!ready"
                        class="text-center text-[11px] text-navy-500"
                    >
                        Yuborish uchun barcha mezonlarni baholang, qarorni
                        tanlang va kamida {{ MIN_AUTHOR }} belgili taqriz
                        yozing.
                    </p>
                </div>
            </form>

            <!-- 3. Yakunlangan taqriz -->
            <div v-else-if="review.status === 'completed'" class="grid gap-4">
                <div
                    class="flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-[13px] font-semibold text-emerald-800"
                >
                    <CircleCheck class="size-4" />
                    Taqriz {{ formatDate(review.completedAt) }} da topshirilgan
                </div>
                <div class="flex items-center justify-between">
                    <StarRating
                        :model-value="review.form.score"
                        readonly
                        size="size-5"
                    />
                    <span class="text-sm font-bold text-navy-900 tabular-nums">
                        {{ review.form.score?.toFixed(1) ?? '—' }} / 5
                    </span>
                </div>
                <dl class="grid gap-2 text-[13px]">
                    <div
                        v-for="criterion in options.criteria"
                        :key="criterion.key"
                        class="flex justify-between gap-3 border-b border-dashed border-line pb-1.5"
                    >
                        <dt class="text-navy-600">{{ criterion.label }}</dt>
                        <dd class="font-bold text-navy-900 tabular-nums">
                            {{
                                review.form.criteria[criterion.key]?.toFixed(
                                    1,
                                ) ?? '—'
                            }}
                        </dd>
                    </div>
                </dl>
                <p class="text-[13px]">
                    <span class="mr-1 text-navy-500">Qaror:</span>
                    <b class="text-navy-900"> {{ recommendationLabel }}</b>
                </p>
                <p
                    class="rounded-lg bg-[#f5f8fc] p-3 text-[13px] leading-relaxed whitespace-pre-line text-navy-700"
                >
                    {{ review.form.comments_to_author }}
                </p>
                <a
                    v-if="review.form.attachmentUrl"
                    :href="review.form.attachmentUrl"
                    :class="cn(secondaryButtonClass, 'h-9 text-xs')"
                >
                    <Download class="size-4" /> Taqriz fayli
                </a>
            </div>

            <!-- 4. Yopilgan -->
            <div
                v-else
                class="flex flex-col items-center gap-2 py-6 text-center text-sm text-navy-500"
            >
                <Lock class="size-8 text-navy-300" />
                Taqriz holati:
                <b class="text-navy-800">{{ review.statusLabel }}</b>
            </div>
        </div>

        <ActionDialog
            v-model:open="declineOpen"
            title="Taklifni rad etish"
            description="Sababni muharrirga ko'rsatish ixtiyoriy (masalan, mavzu sohangizga mos emas yoki manfaatlar to'qnashuvi)."
            :icon="CircleX"
            tone="danger"
            confirm-text="Rad etish"
            :processing="declineForm.processing"
            @confirm="decline"
        >
            <textarea
                v-model="declineForm.reason"
                rows="3"
                maxlength="1000"
                placeholder="Sabab (ixtiyoriy)"
                :class="textareaClass"
            />
        </ActionDialog>
    </aside>
</template>
