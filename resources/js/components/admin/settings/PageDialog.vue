<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, FileText, Info, Plus, Trash2 } from '@lucide/vue';
import { computed, watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import { t } from '@/lib/i18n';
import type { SettingsPage, SettingsPageSection, Translated } from '@/types';
import TranslatableField from './TranslatableField.vue';

/**
 * Statik sahifani tahrirlash: sarlavha, qisqa tavsif (SEO) va bo'limlar
 * (har biri — sarlavha + matn, uch tilda). Bo'limlarni qo'shish, o'chirish, tartiblash.
 */
const props = defineProps<{ page: SettingsPage | null }>();

const open = defineModel<boolean>('open', { default: false });

const MAX_SECTIONS = 20;

type Form = {
    title: Translated;
    description: Translated;
    sections: SettingsPageSection[];
};

const empty = (): Translated => ({ uz: '', ru: '', en: '' });
const copy = (value: Translated): Translated => ({ ...value });

const form = useForm<Form>({
    title: empty(),
    description: empty(),
    sections: [],
});

watch(open, (value) => {
    if (!value || !props.page) {
        return;
    }

    form.defaults({
        title: copy(props.page.title),
        description: copy(props.page.description),
        sections: props.page.sections.map((s) => ({
            heading: copy(s.heading),
            body: copy(s.body),
        })),
    });
    form.reset();
    form.clearErrors();
});

const errors = computed(() => form.errors as Record<string, string>);

function add(): void {
    if (form.sections.length < MAX_SECTIONS) {
        form.sections.push({ heading: empty(), body: empty() });
    }
}

function move(index: number, delta: -1 | 1): void {
    const target = index + delta;

    if (target < 0 || target >= form.sections.length) {
        return;
    }

    const [item] = form.sections.splice(index, 1);
    form.sections.splice(target, 0, item);
}

function remove(index: number): void {
    form.sections.splice(index, 1);
}

function submit(): void {
    if (!props.page) {
        return;
    }

    form.put(props.page.urls.update, {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}

const iconButton =
    'inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700 disabled:pointer-events-none disabled:opacity-30';
</script>

<template>
    <ActionDialog
        v-model:open="open"
        :title="page ? page.label : ''"
        :description="
            t(
                'Matn uch tilda: o\'zbekcha majburiy, rus va ingliz — ixtiyoriy (bo\'lmasa o\'zbekchasi ko\'rsatiladi).',
            )
        "
        :icon="FileText"
        :confirm-text="t('Saqlash')"
        :processing="form.processing"
        size="lg"
        @confirm="submit"
    >
        <div class="grid grid-cols-1 gap-5">
            <TranslatableField
                v-model="form.title"
                :label="t('Sarlavha')"
                field="title"
                :errors="errors"
                required
                :maxlength="150"
            />
            <TranslatableField
                v-model="form.description"
                :label="t('Qisqa tavsif')"
                field="description"
                :errors="errors"
                multiline
                :rows="2"
                :maxlength="300"
                :placeholder="
                    t(
                        'Sarlavha ostida va qidiruv tizimlarida (meta description) ko\'rinadi',
                    )
                "
            />

            <div
                class="flex gap-2.5 rounded-xl border border-brand-100 bg-brand-50/50 px-3.5 py-3 text-xs leading-relaxed text-navy-600"
            >
                <Info class="mt-0.5 size-4 shrink-0 text-brand-600" />
                <p>
                    {{
                        t(
                            "Matnda: xatboshilarni bo'sh qator bilan ajrating; «- » bilan boshlangan qatorlar — ro'yxat, «1. » — raqamli ro'yxat; **qalin**.",
                        )
                    }}
                    {{
                        t(
                            'Avtomatik qiymatlar: {journal} — jurnal nomi, {email} — pochta, {plagiarism_max} — plagiat chegarasi.',
                        )
                    }}
                </p>
            </div>

            <ol class="grid gap-4">
                <li
                    v-for="(section, index) in form.sections"
                    :key="index"
                    class="rounded-xl border border-line bg-[#fafbfd] p-4 transition-colors hover:border-brand-200"
                >
                    <div class="mb-3 flex items-center gap-2">
                        <span
                            class="flex size-6 items-center justify-center rounded-full bg-navy-900 text-[11px] font-bold text-gold-300 tabular-nums"
                            >{{ index + 1 }}</span
                        >
                        <span
                            class="min-w-0 flex-1 truncate text-[13px] font-semibold text-navy-800"
                            >{{ section.heading.uz || t("Yangi bo'lim") }}</span
                        >
                        <button
                            type="button"
                            :class="iconButton"
                            :disabled="index === 0"
                            :aria-label="t('Yuqoriga')"
                            @click="move(index, -1)"
                        >
                            <ArrowUp class="size-4" />
                        </button>
                        <button
                            type="button"
                            :class="iconButton"
                            :disabled="index === form.sections.length - 1"
                            :aria-label="t('Pastga')"
                            @click="move(index, 1)"
                        >
                            <ArrowDown class="size-4" />
                        </button>
                        <button
                            type="button"
                            :class="[
                                iconButton,
                                'hover:bg-red-50 hover:text-red-600',
                            ]"
                            :aria-label="t('O\'chirish')"
                            @click="remove(index)"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </div>
                    <div class="grid gap-3">
                        <TranslatableField
                            v-model="section.heading"
                            :label="t('Bo\'lim sarlavhasi')"
                            :field="`sections.${index}.heading`"
                            :errors="errors"
                            :maxlength="200"
                        />
                        <TranslatableField
                            v-model="section.body"
                            :label="t('Matn')"
                            :field="`sections.${index}.body`"
                            :errors="errors"
                            multiline
                            :rows="7"
                            :maxlength="10000"
                        />
                    </div>
                </li>
            </ol>

            <button
                type="button"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-dashed border-brand-300 bg-white text-[13px] font-semibold text-brand-700 transition-all hover:-translate-y-px hover:border-brand-500 hover:bg-brand-50 disabled:pointer-events-none disabled:opacity-50"
                :disabled="form.sections.length >= MAX_SECTIONS"
                @click="add"
            >
                <Plus class="size-4" /> {{ t("Bo'lim qo'shish") }}
            </button>
            <p v-if="errors.sections" class="text-xs text-red-600">
                {{ errors.sections }}
            </p>
        </div>
    </ActionDialog>
</template>
