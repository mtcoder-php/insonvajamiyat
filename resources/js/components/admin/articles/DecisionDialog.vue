<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { CircleCheck, CircleX, FilePenLine } from '@lucide/vue';
import type { Component } from 'vue';
import { computed, watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import { textareaClass } from '@/lib/formStyles';
import type { EditorialArticle, EditorialDecisionKey } from '@/types';

/**
 * Muharrir qarori oynasi: muallifga izoh (tuzatish va rad etishda majburiy) va ichki izoh.
 */
const props = defineProps<{
    article: EditorialArticle;
    decision: EditorialDecisionKey | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const meta: Record<
    EditorialDecisionKey,
    {
        title: string;
        description: string;
        confirm: string;
        icon: Component;
        tone: 'primary' | 'danger';
        required: boolean;
        placeholder: string;
    }
> = {
    request_revision: {
        title: 'Tuzatish talab qilish',
        description:
            "Maqola muallifga tuzatish uchun qaytariladi. Izoh muallifga ko'rinadi.",
        confirm: 'Tuzatishga qaytarish',
        icon: FilePenLine,
        tone: 'primary',
        required: true,
        placeholder:
            "Masalan: annotatsiyani qisqartiring, adabiyotlar ro'yxatini GOST talablariga moslang...",
    },
    accept: {
        title: 'Maqolani qabul qilish',
        description:
            "Maqola nashrga qabul qilinadi va nashrga tayyorlash bosqichiga o'tadi.",
        confirm: 'Qabul qilish',
        icon: CircleCheck,
        tone: 'primary',
        required: false,
        placeholder: "Ixtiyoriy — muallifga tabrik yoki qo'shimcha ma'lumot",
    },
    reject: {
        title: 'Maqolani rad etish',
        description:
            "Maqola rad etiladi va jarayon yakunlanadi. Sabab muallifga ko'rinadi.",
        confirm: 'Rad etish',
        icon: CircleX,
        tone: 'danger',
        required: true,
        placeholder:
            "Rad etish sababi (mavzu jurnal yo'nalishiga mos emas, ilmiy yangilik yo'q...)",
    },
};

const current = computed(() => (props.decision ? meta[props.decision] : null));

const form = useForm({
    decision: '' as string,
    comment_to_author: '',
    internal_note: '',
});

watch(open, (value) => {
    if (value) {
        form.reset();
        form.clearErrors();
        form.decision = props.decision ?? '';
    }
});

const errors = computed(() => form.errors as Record<string, string>);

function submit(): void {
    form.post(props.article.urls.decision, {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <ActionDialog
        v-if="current"
        v-model:open="open"
        :title="current.title"
        :description="current.description"
        :icon="current.icon"
        :tone="current.tone"
        :confirm-text="current.confirm"
        :processing="form.processing"
        @confirm="submit"
    >
        <div class="grid gap-4">
            <p
                v-if="errors.decision"
                class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
            >
                {{ errors.decision }}
            </p>
            <FormField
                label="Muallifga izoh"
                for="decision-comment"
                :required="current.required"
                :error="errors.comment_to_author"
            >
                <textarea
                    id="decision-comment"
                    v-model="form.comment_to_author"
                    rows="5"
                    maxlength="5000"
                    :placeholder="current.placeholder"
                    :aria-invalid="!!errors.comment_to_author"
                    :class="textareaClass"
                />
            </FormField>
            <FormField
                label="Ichki izoh"
                for="decision-note"
                hint="Faqat tahririyat ko'radi (ixtiyoriy)"
                :error="errors.internal_note"
            >
                <textarea
                    id="decision-note"
                    v-model="form.internal_note"
                    rows="2"
                    maxlength="2000"
                    :class="textareaClass"
                />
            </FormField>
        </div>
    </ActionDialog>
</template>
