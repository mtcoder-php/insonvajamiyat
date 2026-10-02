<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    BadgeCheck,
    CalendarClock,
    ExternalLink,
    FileUp,
    LayoutTemplate,
    LoaderCircle,
    Send,
    ShieldAlert,
    UserRoundCheck,
    Undo2,
    XCircle,
} from '@lucide/vue';
import { ref } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import { textareaClass } from '@/lib/formStyles';
import { formatDate, formatDateTime } from '@/lib/format';
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
const waiveOpen = ref(false);
const waiveForm = useForm({ reason: '' });

function waive(): void {
    waiveForm.post(props.article.urls.waiveProof, {
        preserveScroll: true,
        onSuccess: () => {
            waiveOpen.value = false;
            waiveForm.reset();
        },
    });
}

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

            <!-- Korrektura muddati -->
            <div
                v-if="
                    article.status === 'in_production' &&
                    (article.production.proof.state === 'pending' ||
                        article.production.proof.state === 'overdue')
                "
                :class="
                    cn(
                        'flex items-start gap-3 rounded-lg px-4 py-2.5 text-[13px] ring-1',
                        article.production.proof.state === 'overdue'
                            ? 'bg-red-50 text-red-800 ring-red-200'
                            : 'bg-amber-50 text-amber-900 ring-amber-200',
                    )
                "
            >
                <ShieldAlert
                    v-if="article.production.proof.state === 'overdue'"
                    class="mt-0.5 size-[18px] shrink-0"
                />
                <CalendarClock v-else class="mt-0.5 size-[18px] shrink-0" />
                <span>
                    <b class="block">{{
                        article.production.proof.state === 'overdue'
                            ? "Korrektura muddati o'tdi"
                            : 'Muallif javobi kutilmoqda'
                    }}</b>
                    <span class="text-xs"
                        >Muddat:
                        {{
                            formatDateTime(article.production.proof.dueAt)
                        }}</span
                    >
                </span>
            </div>
            <div
                v-else-if="article.production.proof.state === 'waived'"
                class="flex items-start gap-3 rounded-lg bg-sky-50 px-4 py-2.5 text-[13px] text-sky-900 ring-1 ring-sky-200"
            >
                <UserRoundCheck class="mt-0.5 size-[18px] shrink-0" />
                <span class="min-w-0">
                    <b class="block">Muallifsiz tasdiqlangan</b>
                    <span class="text-xs"
                        >{{ article.production.proof.waived?.by }} ·
                        {{
                            formatDate(article.production.proof.waived?.at)
                        }}</span
                    >
                    <span
                        v-if="article.production.proof.waived?.reason"
                        class="mt-1 block text-xs whitespace-pre-line text-sky-800"
                        >{{ article.production.proof.waived.reason }}</span
                    >
                </span>
            </div>
            <button
                v-if="article.can.waiveProof"
                type="button"
                :class="
                    cn(
                        item,
                        'border border-red-200 bg-white text-red-700 hover:border-red-300 hover:bg-red-50',
                    )
                "
                @click="waiveOpen = true"
            >
                <UserRoundCheck class="size-[18px]" />
                Muallifsiz tasdiqlash
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
        <ActionDialog
            v-model:open="waiveOpen"
            title="Korrekturani muallifsiz tasdiqlash"
            description="Muallif belgilangan muddatda javob bermadi. Qaror sababini yozing — u muallifga yuboriladi va audit log'ga yoziladi."
            :icon="UserRoundCheck"
            confirm-text="Tasdiqlash"
            :processing="waiveForm.processing"
            @confirm="waive"
        >
            <textarea
                v-model="waiveForm.reason"
                rows="4"
                maxlength="1000"
                placeholder="Masalan: muallifga email va telefon orqali murojaat qilindi, javob bo'lmadi; son chop etish muddati yaqin."
                :class="textareaClass"
            />
            <p v-if="waiveForm.errors.reason" class="mt-1 text-xs text-red-600">
                {{ waiveForm.errors.reason }}
            </p>
        </ActionDialog>
    </section>
</template>
