<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Check, Search, Sparkles, UsersRound } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import UserAvatar from '@/components/users/UserAvatar.vue';
import { inputClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import type { EditorialArticle, ReviewerOption } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Taqrizchilarni taklif qilish: ro'yxatdan tanlash (joriy yuklama bilan) va muddat.
 */
const props = defineProps<{
    article: EditorialArticle;
    reviewers: ReviewerOption[];
}>();

const open = defineModel<boolean>('open', { default: false });

const MAX = 5;

const form = useForm<{ reviewer_ids: number[]; due_days: number }>({
    reviewer_ids: [],
    due_days: 14,
});

const search = ref('');

// Joriy taqriz raundida allaqachon taklif qilinganlar (bitta raundda takror taklif yo'q).
// Maqola hali "Taqrizda" bo'lmasa — yangi raund boshlanadi.
const taken = computed(
    () =>
        new Set(
            props.article.status === 'in_review'
                ? props.article.reviews
                      .filter(
                          (review) =>
                              review.round === props.article.reviewRound,
                      )
                      .map((review) => review.reviewerId)
                : [],
        ),
);

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();

    return props.reviewers.filter(
        (r) =>
            !q ||
            r.name.toLowerCase().includes(q) ||
            (r.organization ?? '').toLowerCase().includes(q),
    );
});

watch(open, (value) => {
    if (value) {
        form.reset();
        form.clearErrors();
        search.value = '';
    }
});

function toggle(id: number): void {
    form.reviewer_ids = form.reviewer_ids.includes(id)
        ? form.reviewer_ids.filter((x) => x !== id)
        : form.reviewer_ids.length < MAX
          ? [...form.reviewer_ids, id]
          : form.reviewer_ids;
}

const errors = computed(() => form.errors as Record<string, string>);

function submit(): void {
    form.post(props.article.urls.invite, {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <ActionDialog
        v-model:open="open"
        :title="t('Taqrizchilarni tayinlash')"
        :description="
            t(
                'Taqrizchilarga taklif yuboriladi. Ular taklifni qabul qilgach, maqola fayllari ochiladi. Taqrizchi va muallif bir-birini ko\'rmaydi (blind review).',
            )
        "
        :icon="UsersRound"
        :confirm-text="t('Taklif yuborish')"
        :processing="form.processing"
        @confirm="submit"
    >
        <div class="grid gap-4">
            <p
                v-if="errors.reviewer_ids"
                class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
            >
                {{ errors.reviewer_ids }}
            </p>
            <label class="relative block">
                <span class="sr-only">{{ t('Qidirish') }}</span>
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                />
                <input
                    v-model="search"
                    type="search"
                    :placeholder="t('Ism, tashkilot yoki yo\'nalish...')"
                    :class="cn(inputClass, 'h-9 pl-9 text-[13px]')"
                />
            </label>
            <ul
                class="-mx-1 grid max-h-64 gap-1 overflow-y-auto px-1"
                role="listbox"
                aria-multiselectable="true"
            >
                <li v-for="reviewer in filtered" :key="reviewer.id">
                    <button
                        type="button"
                        role="option"
                        :aria-selected="form.reviewer_ids.includes(reviewer.id)"
                        :disabled="taken.has(reviewer.id)"
                        :class="
                            cn(
                                'flex w-full items-center gap-3 rounded-lg border px-3 py-2 text-left transition-colors disabled:cursor-not-allowed disabled:opacity-50',
                                form.reviewer_ids.includes(reviewer.id)
                                    ? 'border-brand-400 bg-brand-50'
                                    : 'border-line hover:border-brand-200',
                            )
                        "
                        @click="toggle(reviewer.id)"
                    >
                        <UserAvatar :name="reviewer.name" size="sm" />
                        <span class="min-w-0 flex-1">
                            <span
                                class="flex items-center gap-1.5 text-[13px] font-semibold text-navy-900"
                            >
                                <span class="truncate">{{
                                    reviewer.name
                                }}</span>
                                <span
                                    v-if="reviewer.matches"
                                    class="inline-flex shrink-0 items-center gap-0.5 rounded bg-emerald-50 px-1.5 py-px text-[10px] font-semibold text-emerald-700"
                                    :title="reviewer.subjects.join(', ')"
                                >
                                    <Sparkles class="size-3" />
                                    {{ t("Mos yo'nalish") }}
                                </span>
                            </span>
                            <span
                                class="block truncate text-[11px] text-navy-500"
                            >
                                {{ reviewer.organization ?? '—' }} ·
                                {{
                                    t(
                                        'faol: :active · yakunlangan: :completed',
                                        {
                                            active: reviewer.active,
                                            completed: reviewer.completed,
                                        },
                                    )
                                }}
                            </span>
                        </span>
                        <span
                            :class="
                                cn(
                                    'flex size-5 shrink-0 items-center justify-center rounded border',
                                    form.reviewer_ids.includes(reviewer.id)
                                        ? 'border-brand-600 bg-brand-600 text-white'
                                        : 'border-navy-200',
                                )
                            "
                        >
                            <Check
                                v-if="form.reviewer_ids.includes(reviewer.id)"
                                class="size-3.5"
                                :stroke-width="3"
                            />
                        </span>
                    </button>
                </li>
                <li
                    v-if="!filtered.length"
                    class="py-6 text-center text-xs text-navy-500"
                >
                    {{
                        t(
                            "Taqrizchi topilmadi. Taqrizchilarni «Taqrizchilar» bo'limida qo'shing (to'xtatilganlar bu yerda chiqmaydi).",
                        )
                    }}
                </li>
            </ul>
            <p class="text-xs text-navy-500">
                {{
                    t('Tanlangan: :count / :max', {
                        count: form.reviewer_ids.length,
                        max: MAX,
                    })
                }}
            </p>
            <FormField
                :label="t('Taqriz muddati (kun)')"
                for="due-days"
                required
                :error="errors.due_days"
            >
                <input
                    id="due-days"
                    v-model.number="form.due_days"
                    type="number"
                    min="3"
                    max="60"
                    :class="cn(inputClass, 'w-32 tabular-nums')"
                />
            </FormField>
        </div>
    </ActionDialog>
</template>
