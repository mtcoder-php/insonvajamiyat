<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { PenLine } from '@lucide/vue';
import { computed, watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import { inputClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import type {
    IssueOption,
    ProductionArticle,
    ProductionMetadataForm,
} from '@/types';
import { t } from '@/lib/i18n';

/**
 * Nashr meta ma'lumotlari: DOI, UDK, plagiat foizi, jurnal soni va sahifalar.
 */
const props = defineProps<{
    article: ProductionArticle;
    issues: IssueOption[];
}>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm<ProductionMetadataForm>({ ...props.article.form });

watch(open, (value) => {
    if (value) {
        form.defaults({ ...props.article.form });
        form.reset();
        form.clearErrors();
    }
});

const errors = computed(() => form.errors as Record<string, string>);

// Yakuniy PDF dagi betlar soni ma'lum bo'lsa — oxirgi bet avtomatik hisoblanadi
const pdfPages = computed(() => props.article.finalPdf?.pageCount ?? null);

watch(
    () => [form.page_from, pdfPages.value] as const,
    ([from, count]) => {
        if (count && from) {
            form.page_to = from + count - 1;
        }
    },
);

const pages = computed(() =>
    form.page_from && form.page_to && form.page_to >= form.page_from
        ? form.page_to - form.page_from + 1
        : null,
);

function submit(): void {
    form.put(props.article.urls.metadata, {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <ActionDialog
        v-model:open="open"
        :title="t('Nashr ma\'lumotlari')"
        :description="
            t(
                'O\'zgartirilsa bosh muharrir tasdig\'i bekor bo\'ladi va qayta tasdiqlash kerak bo\'ladi.',
            )
        "
        :icon="PenLine"
        :confirm-text="t('Saqlash')"
        :processing="form.processing"
        @confirm="submit"
    >
        <div class="grid gap-4">
            <FormField
                label="DOI"
                for="doi"
                :hint="t('Masalan: 10.5281/insonvajamiyat.2026.0048')"
                :error="errors.doi"
            >
                <input
                    id="doi"
                    v-model.trim="form.doi"
                    type="text"
                    :class="cn(inputClass, 'font-mono text-[13px]')"
                />
            </FormField>
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField label="UDK" for="udc" :error="errors.udc">
                    <input
                        id="udc"
                        v-model.trim="form.udc"
                        type="text"
                        :class="inputClass"
                    />
                </FormField>
                <FormField
                    :label="t('Plagiat, %')"
                    for="plagiarism"
                    :hint="
                        t('Chegara: :value%', { value: article.plagiarismMax })
                    "
                    :error="errors.plagiarism_percent"
                >
                    <input
                        id="plagiarism"
                        v-model.number="form.plagiarism_percent"
                        type="number"
                        min="0"
                        max="100"
                        step="0.1"
                        :class="cn(inputClass, 'tabular-nums')"
                    />
                </FormField>
            </div>
            <FormField
                :label="t('Jurnal soni')"
                for="issue"
                :error="errors.issue_id"
                :hint="
                    issues.length
                        ? undefined
                        : t(
                              'Jurnal sonlari hali yo\'q — «Jurnallar» bo\'limida yaratiladi.',
                          )
                "
            >
                <SelectInput id="issue" v-model="form.issue_id">
                    <option :value="null">
                        {{ t('— Biriktirilmagan —') }}
                    </option>
                    <option
                        v-for="issue in issues"
                        :key="issue.id"
                        :value="issue.id"
                    >
                        {{ issue.label }} · {{ issue.statusLabel }}
                    </option>
                </SelectInput>
            </FormField>
            <p
                v-if="pdfPages"
                class="-mb-1 rounded-lg bg-brand-50 px-3 py-2 text-xs text-brand-800"
            >
                {{
                    t(
                        "Yakuniy PDF da :pages bet. Odatda sahifalarni «Jurnallar» bo'limida son bo'yicha avtomatik hisoblash yetarli — bu yerda faqat istisno holatda boshlang'ich betni kiriting.",
                        { pages: pdfPages },
                    )
                }}
            </p>
            <div class="grid grid-cols-2 gap-4">
                <FormField
                    :label="t('Boshlang\'ich bet')"
                    for="page-from"
                    :error="errors.page_from"
                >
                    <input
                        id="page-from"
                        v-model.number="form.page_from"
                        type="number"
                        min="1"
                        :disabled="!form.issue_id"
                        :class="cn(inputClass, 'tabular-nums')"
                    />
                </FormField>
                <FormField
                    :label="t('Oxirgi bet')"
                    for="page-to"
                    :error="errors.page_to"
                    :hint="
                        pdfPages
                            ? t('PDF dan avtomatik: :pdfPages bet', {
                                  pdfPages: pdfPages,
                              })
                            : pages
                              ? t(':pages bet', { pages: pages })
                              : undefined
                    "
                >
                    <input
                        id="page-to"
                        v-model.number="form.page_to"
                        type="number"
                        min="1"
                        :readonly="pdfPages !== null"
                        :disabled="!form.issue_id"
                        :class="cn(inputClass, 'tabular-nums')"
                    />
                </FormField>
            </div>
        </div>
    </ActionDialog>
</template>
