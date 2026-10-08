<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    Mail,
    Trash2,
    UserPlus,
    UserRound,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import FormField from '@/components/admin/ui/FormField.vue';
import WizardFooter from '@/components/cabinet/wizard/WizardFooter.vue';
import { inputClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import type {
    ArticleDraft,
    DraftAuthor,
    WizardLimits,
    WizardProps,
} from '@/types';
import { t } from '@/lib/i18n';

/**
 * 2-bosqich: mualliflar (tartib — maqoladagi tartib), aloqa uchun mas'ul muallif.
 * "Men" — yuboruvchining o'zi (profildan to'ldiriladi).
 */
const props = defineProps<{
    article: ArticleDraft;
    me: WizardProps['me'];
    limits: WizardLimits;
    prevHref: string | null;
}>();

type AuthorRow = Omit<DraftAuthor, 'is_corresponding'>;

const blank = (): AuthorRow => ({
    last_name: '',
    first_name: '',
    middle_name: null,
    email: null,
    organization: null,
    position: null,
    academic_degree: null,
    orcid: null,
    is_me: false,
});

const initial: AuthorRow[] = props.article.authors.map((author) => ({
    last_name: author.last_name,
    first_name: author.first_name,
    middle_name: author.middle_name,
    email: author.email,
    organization: author.organization,
    position: author.position,
    academic_degree: author.academic_degree,
    orcid: author.orcid,
    is_me: author.is_me,
}));

const form = useForm({
    authors: initial.length ? initial : [{ ...props.me, is_me: true }],
    corresponding: Math.max(
        0,
        props.article.authors.findIndex((author) => author.is_corresponding),
    ),
});

// Ro'yxat animatsiyasi uchun barqaror kalitlar (serverga yuborilmaydi)
let nextKey = 0;
const keys = ref<number[]>(form.authors.map(() => nextKey++));

const hasMe = computed(() => form.authors.some((author) => author.is_me));
const canAdd = computed(() => form.authors.length < props.limits.maxAuthors);

function add(author: AuthorRow): void {
    if (canAdd.value) {
        form.authors.push(author);
        keys.value.push(nextKey++);
    }
}

function remove(index: number): void {
    form.authors.splice(index, 1);
    keys.value.splice(index, 1);

    if (form.corresponding === index) {
        form.corresponding = 0;
    } else if (form.corresponding > index) {
        form.corresponding--;
    }
}

function move(index: number, offset: -1 | 1): void {
    const target = index + offset;
    const [row] = form.authors.splice(index, 1);
    form.authors.splice(target, 0, row);
    const [key] = keys.value.splice(index, 1);
    keys.value.splice(target, 0, key);

    if (form.corresponding === index) {
        form.corresponding = target;
    } else if (form.corresponding === target) {
        form.corresponding = index;
    }
}

const error = (index: number, field: string): string | undefined =>
    (form.errors as Record<string, string>)[`authors.${index}.${field}`];

function save(stay: boolean): void {
    form.transform((data) => ({ ...data, stay })).put(
        props.article.urls.authors,
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <form class="grid gap-4" @submit.prevent="save(false)">
        <p
            v-if="form.errors.authors || form.errors.corresponding"
            class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
        >
            {{ form.errors.authors ?? form.errors.corresponding }}
        </p>

        <TransitionGroup tag="ol" name="author" class="grid gap-4">
            <li
                v-for="(author, index) in form.authors"
                :key="keys[index]"
                :class="
                    cn(
                        'group rounded-xl border bg-white p-4 transition-all duration-300 sm:p-5',
                        form.corresponding === index
                            ? 'border-brand-300 shadow-[0_14px_30px_-22px_rgba(0,108,246,0.9)]'
                            : 'border-line hover:border-brand-200 hover:shadow-[0_12px_28px_-22px_rgba(0,36,66,0.5)]',
                    )
                "
            >
                <header class="mb-4 flex flex-wrap items-center gap-3">
                    <span
                        class="flex size-9 items-center justify-center rounded-full bg-navy-950 text-sm font-bold text-white tabular-nums transition-transform group-hover:scale-105"
                    >
                        {{ index + 1 }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-navy-950">
                            {{
                                [author.last_name, author.first_name]
                                    .filter(Boolean)
                                    .join(' ') || t('Yangi muallif')
                            }}
                            <span
                                v-if="author.is_me"
                                class="ml-1.5 rounded bg-brand-50 px-1.5 py-0.5 align-middle text-[10px] font-semibold text-brand-700"
                                >{{ t('Men') }}</span
                            >
                        </p>
                        <label
                            class="mt-1 inline-flex cursor-pointer items-center gap-1.5 text-xs text-navy-600"
                        >
                            <input
                                v-model="form.corresponding"
                                type="radio"
                                name="corresponding"
                                :value="index"
                                class="size-3.5 accent-brand-600"
                            />
                            <Mail class="size-3.5" />
                            {{ t("Aloqa uchun mas'ul muallif") }}
                        </label>
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            class="flex size-8 items-center justify-center rounded-lg border border-line text-navy-500 transition-colors hover:border-brand-200 hover:text-brand-700 disabled:opacity-30"
                            :disabled="index === 0"
                            :aria-label="t('Yuqoriga')"
                            @click="move(index, -1)"
                        >
                            <ArrowUp class="size-4" />
                        </button>
                        <button
                            type="button"
                            class="flex size-8 items-center justify-center rounded-lg border border-line text-navy-500 transition-colors hover:border-brand-200 hover:text-brand-700 disabled:opacity-30"
                            :disabled="index === form.authors.length - 1"
                            :aria-label="t('Pastga')"
                            @click="move(index, 1)"
                        >
                            <ArrowDown class="size-4" />
                        </button>
                        <button
                            type="button"
                            class="flex size-8 items-center justify-center rounded-lg border border-line text-navy-500 transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-600 disabled:opacity-30"
                            :disabled="form.authors.length === 1"
                            :aria-label="t('Muallifni olib tashlash')"
                            @click="remove(index)"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </div>
                </header>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <FormField
                        :label="t('Familiya')"
                        required
                        :error="error(index, 'last_name')"
                    >
                        <input
                            v-model="author.last_name"
                            type="text"
                            maxlength="100"
                            :aria-invalid="!!error(index, 'last_name')"
                            :class="inputClass"
                        />
                    </FormField>
                    <FormField
                        :label="t('Ism')"
                        required
                        :error="error(index, 'first_name')"
                    >
                        <input
                            v-model="author.first_name"
                            type="text"
                            maxlength="100"
                            :aria-invalid="!!error(index, 'first_name')"
                            :class="inputClass"
                        />
                    </FormField>
                    <FormField
                        :label="t('Otasining ismi')"
                        :error="error(index, 'middle_name')"
                    >
                        <input
                            v-model="author.middle_name"
                            type="text"
                            maxlength="100"
                            :class="inputClass"
                        />
                    </FormField>
                    <FormField
                        :label="t('Elektron pochta')"
                        :required="form.corresponding === index"
                        :error="error(index, 'email')"
                    >
                        <input
                            v-model="author.email"
                            type="email"
                            maxlength="255"
                            :aria-invalid="!!error(index, 'email')"
                            :class="inputClass"
                        />
                    </FormField>
                    <FormField
                        :label="t('Tashkilot')"
                        required
                        :error="error(index, 'organization')"
                        class="xl:col-span-2"
                    >
                        <input
                            v-model="author.organization"
                            type="text"
                            maxlength="255"
                            :placeholder="
                                t('Masalan: O\'zbekiston Milliy universiteti')
                            "
                            :aria-invalid="!!error(index, 'organization')"
                            :class="inputClass"
                        />
                    </FormField>
                    <FormField
                        :label="t('Lavozim')"
                        :error="error(index, 'position')"
                    >
                        <input
                            v-model="author.position"
                            type="text"
                            maxlength="255"
                            :class="inputClass"
                        />
                    </FormField>
                    <FormField
                        :label="t('Ilmiy daraja')"
                        :error="error(index, 'academic_degree')"
                    >
                        <input
                            v-model="author.academic_degree"
                            type="text"
                            maxlength="100"
                            :placeholder="t('PhD, DSc...')"
                            :class="inputClass"
                        />
                    </FormField>
                    <FormField label="ORCID" :error="error(index, 'orcid')">
                        <input
                            v-model="author.orcid"
                            type="text"
                            maxlength="19"
                            placeholder="0000-0000-0000-0000"
                            :aria-invalid="!!error(index, 'orcid')"
                            :class="cn(inputClass, 'font-mono')"
                        />
                    </FormField>
                </div>
            </li>
        </TransitionGroup>

        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                class="group inline-flex h-10 items-center gap-2 rounded-lg border border-dashed border-brand-300 bg-brand-50/50 px-4 text-sm font-semibold text-brand-700 transition-all hover:-translate-y-px hover:bg-brand-50 disabled:pointer-events-none disabled:opacity-50"
                :disabled="!canAdd"
                @click="add(blank())"
            >
                <UserPlus
                    class="size-4 transition-transform group-hover:scale-110"
                />
                {{ t("Hammuallif qo'shish") }}
            </button>
            <button
                v-if="!hasMe"
                type="button"
                class="inline-flex h-10 items-center gap-2 rounded-lg border border-line px-4 text-sm font-medium text-navy-700 transition-all hover:-translate-y-px hover:border-brand-200 hover:text-brand-700 disabled:opacity-50"
                :disabled="!canAdd"
                @click="add({ ...me, is_me: true })"
            >
                <UserRound class="size-4" />
                {{ t("O'zimni qo'shish") }}
            </button>
            <span class="self-center text-xs text-navy-400">
                {{ form.authors.length }} / {{ limits.maxAuthors }}
            </span>
        </div>

        <WizardFooter
            :prev-href="prevHref"
            :processing="form.processing"
            @next="save(false)"
            @draft="save(true)"
        />
    </form>
</template>

<style scoped>
.author-move,
.author-enter-active,
.author-leave-active {
    transition: all 0.3s ease;
}

.author-enter-from,
.author-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
