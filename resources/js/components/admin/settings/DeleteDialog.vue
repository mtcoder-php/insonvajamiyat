<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import { t } from '@/lib/i18n';

/** O'chirishni tasdiqlash (server xatosi — masalan, maqolasi bor yo'nalish — shu yerda ko'rsatiladi) */
const props = defineProps<{
    url: string | null;
    title: string;
    description: string;
}>();

const open = defineModel<boolean>('open', { default: false });
const processing = ref(false);
const error = ref<string | null>(null);

function confirm(): void {
    if (!props.url) {
        return;
    }

    processing.value = true;
    error.value = null;
    router.delete(props.url, {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
        onError: (errors) =>
            (error.value = Object.values(errors)[0] ?? t("O'chirib bo'lmadi.")),
        onFinish: () => (processing.value = false),
    });
}
</script>

<template>
    <ActionDialog
        v-model:open="open"
        :title="title"
        :description="description"
        :icon="Trash2"
        tone="danger"
        :confirm-text="t('O\'chirish')"
        :processing="processing"
        @confirm="confirm"
    >
        <p
            v-if="error"
            class="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700"
        >
            {{ error }}
        </p>
    </ActionDialog>
</template>
