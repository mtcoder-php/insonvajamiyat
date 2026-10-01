<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    BadgeCheck,
    ExternalLink,
    FileUp,
    LayoutTemplate,
    LoaderCircle,
    Send,
    Undo2,
    XCircle,
} from '@lucide/vue';
import { ref } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import { textareaClass } from '@/lib/formStyles';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ProductionArticle } from '@/types';

/**
 * "Final amallar": maketga olish, yakuniy PDF, bosh muharrir tasdig'i, maketdan qaytarish.
 */
const props = defineProps<{ article: ProductionArticle }>();

const emit = defineEmits<{ upload: [] }>();

const busy = ref<string | null>(null);
const cancelOpen = ref(false);
const cancelForm = useForm({ reason: '' });

function post(key: 'start' | 'approve' | 'revoke' | 'publish'): void {
    busy.value = key;
    router.post(
        props.article.urls[key],
        {},
        { preserveScroll: true, onFinish: () => (busy.value = null) },
    );
}

function cancel(): void {
    cancelForm.post(props.article.urls.cancel, {
        preserveScroll: true,
        onSuccess: () => (cancelOpen.value = false),
    });
}

const item =
    'flex h-11 w-full items-center gap-3 rounded-lg px-4 text-[13px] font-semibold transition-all hover:-translate-y-px disabled:pointer-events-none disabled:opacity-50';
</script>

<template>
    <section
        class="rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
    >
        <h2 class="mb-4 font-sans text-[15px] font-bold text-navy-950">
            Final amallar
        </h2>
        <div class="grid gap-2.5">
            <button
                v-if="article.can.start"
                type="button"
                :disabled="busy !== null"
                :class="
                    cn(
                        item,
                        'bg-brand-600 text-white shadow-[0_10px_22px_-12px_rgba(0,108,246,0.9)] hover:bg-brand-500',
                    )
                "
                @click="post('start')"
            >
                <LoaderCircle
                    v-if="busy === 'start'"
                    class="size-[18px] animate-spin"
                />
                <LayoutTemplate v-else class="size-[18px]" />
                Maketga olish
            </button>

            <button
                v-if="article.can.manage"
                type="button"
                :class="
                    cn(
                        item,
                        'bg-brand-600 text-white shadow-[0_10px_22px_-12px_rgba(0,108,246,0.9)] hover:bg-brand-500',
                    )
                "
                @click="emit('upload')"
            >
                <FileUp class="size-[18px]" />
                {{
                    article.finalPdf
                        ? 'Yangi PDF versiya yuklash'
                        : 'Yakuniy PDF yuklash'
                }}
            </button>

            <div
                v-if="article.production.approvedAt"
                class="flex items-center gap-3 rounded-lg bg-emerald-50 px-4 py-2.5 text-[13px] text-emerald-800 ring-1 ring-emerald-200"
            >
                <BadgeCheck class="size-[18px] shrink-0 text-emerald-600" />
                <span>
                    <b class="block">Bosh muharrir tasdiqlagan</b>
                    <span class="text-xs"
                        >{{ article.production.approvedBy }} ·
                        {{ formatDate(article.production.approvedAt) }}</span
                    >
                </span>
            </div>
            <button
                v-else-if="article.status === 'in_production'"
                type="button"
                :disabled="!article.can.approve || busy !== null"
                :title="
                    article.ready
                        ? undefined
                        : 'Avval tekshiruvning barcha bandlarini bajaring'
                "
                :class="
                    cn(
                        item,
                        'border border-emerald-200 bg-emerald-50 text-emerald-800 hover:border-emerald-300 hover:bg-emerald-100',
                    )
                "
                @click="post('approve')"
            >
                <LoaderCircle
                    v-if="busy === 'approve'"
                    class="size-[18px] animate-spin"
                />
                <BadgeCheck v-else class="size-[18px] text-emerald-600" />
                Bosh muharrir tasdig'i
            </button>
            <button
                v-if="article.can.revoke"
                type="button"
                :disabled="busy !== null"
                :class="
                    cn(
                        item,
                        'border border-line text-navy-700 hover:border-amber-300 hover:text-amber-800',
                    )
                "
                @click="post('revoke')"
            >
                <Undo2 class="size-[18px]" />
                Tasdiqni bekor qilish
            </button>

            <a
                v-if="article.urls.public"
                :href="article.urls.public"
                target="_blank"
                rel="noopener"
                :class="
                    cn(
                        item,
                        'bg-emerald-600 text-white shadow-[0_10px_22px_-12px_rgba(5,150,105,0.9)] hover:bg-emerald-500',
                    )
                "
            >
                <ExternalLink class="size-[18px]" />
                Saytda ko'rish
            </a>
            <button
                v-else
                type="button"
                :disabled="!article.can.publish || busy !== null"
                :title="
                    article.can.publish
                        ? 'Chop etilgan songa qo\'shilgan maqolani alohida chop etish'
                        : 'Maqola jurnal soni bilan birga chop etiladi («Jurnallar» → «Sonni chop etish»)'
                "
                :class="cn(item, 'bg-navy-900 text-white hover:bg-brand-700')"
                @click="post('publish')"
            >
                <LoaderCircle
                    v-if="busy === 'publish'"
                    class="size-[18px] animate-spin"
                />
                <Send v-else class="size-[18px]" />
                Nashr qilish
            </button>

            <button
                v-if="article.can.cancel"
                type="button"
                :class="
                    cn(
                        item,
                        'border border-red-200 bg-red-50/60 text-red-700 hover:border-red-300 hover:bg-red-50',
                    )
                "
                @click="cancelOpen = true"
            >
                <XCircle class="size-[18px]" />
                Maketdan qaytarish
            </button>
        </div>

        <ActionDialog
            v-model:open="cancelOpen"
            title="Maketdan qaytarish"
            description="Maqola «Qabul qilindi» holatiga qaytadi, bosh muharrir tasdig'i bekor bo'ladi. Yuklangan fayllar saqlanadi. Bu muallifga ko'rinmaydi."
            :icon="XCircle"
            tone="danger"
            confirm-text="Qaytarish"
            :processing="cancelForm.processing"
            @confirm="cancel"
        >
            <textarea
                v-model="cancelForm.reason"
                rows="3"
                maxlength="1000"
                placeholder="Sabab (ixtiyoriy, ichki izoh)"
                :class="textareaClass"
            />
        </ActionDialog>
    </section>
</template>
