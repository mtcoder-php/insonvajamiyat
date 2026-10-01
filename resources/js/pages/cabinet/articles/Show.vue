<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Download,
    ExternalLink,
    FileText,
    History,
    PenLine,
    Mail,
    MessagesSquare,
    Route,
    Star,
    Trash2,
    Undo2,
    UsersRound,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import ArticlePaymentCard from '@/components/cabinet/ArticlePaymentCard.vue';
import MessageThread from '@/components/articles/MessageThread.vue';
import ArticleReviewsCard from '@/components/cabinet/ArticleReviewsCard.vue';
import ArticleStatusPill from '@/components/cabinet/ArticleStatusPill.vue';
import CabinetPageHeader from '@/components/cabinet/CabinetPageHeader.vue';
import RevisionCard from '@/components/cabinet/RevisionCard.vue';
import StatusTimeline from '@/components/cabinet/StatusTimeline.vue';
import InputError from '@/components/InputError.vue';
import { textareaClass } from '@/lib/formStyles';
import { formatDate, formatFileSize, formatTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import { index, show, withdraw } from '@/routes/cabinet/articles';
import type {
    AuthorArticleDetails,
    AuthorArticlePayment,
    ArticleThread,
    AuthorReview,
    RevisionRequest,
    TimelineStep,
} from '@/types';

/**
 * Muallif kabineti — maqola sahifasi: holat (timeline), ma'lumotlar, mualliflar,
 * fayllar, holat tarixi va maqolani qaytarib olish.
 */
const props = defineProps<{
    article: AuthorArticleDetails;
    steps: TimelineStep[];
    payment: AuthorArticlePayment | null;
    reviews: AuthorReview[];
    revision: RevisionRequest | null;
    messages: ArticleThread;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Mening maqolalarim', href: index() }],
    },
});

const languages: Record<string, string> = {
    uz: "O'zbek",
    ru: 'Rus',
    en: 'Ingliz',
};

const details = computed(() =>
    [
        { label: "Yo'nalish", value: props.article.subject },
        { label: 'Maqola turi', value: props.article.type },
        {
            label: 'Til',
            value:
                languages[props.article.language ?? ''] ??
                props.article.language,
        },
        { label: 'Jurnal soni', value: props.article.issue },
        {
            label: 'Yuborilgan sana',
            value: props.article.submittedAt
                ? `${formatDate(props.article.submittedAt)} ${formatTime(props.article.submittedAt)}`
                : null,
        },
        { label: "To'lov holati", value: props.article.paymentStatusLabel },
        {
            label: 'Taqriz bosqichi',
            value:
                props.article.reviewRound > 0
                    ? `${props.article.reviewRound}-bosqich`
                    : null,
        },
        { label: 'UDK', value: props.article.udc },
        { label: 'DOI', value: props.article.doi },
    ].filter((item) => item.value),
);

const deleteOpen = ref(false);
const deleting = ref(false);

function destroyDraft(): void {
    if (!props.article.destroyUrl) {
        return;
    }

    deleting.value = true;
    router.delete(props.article.destroyUrl, {
        onFinish: () => (deleting.value = false),
    });
}

const withdrawOpen = ref(false);
const withdrawForm = useForm({ reason: '' });

function submitWithdraw(): void {
    withdrawForm.post(withdraw.url(props.article.uuid), {
        preserveScroll: true,
        onSuccess: () => {
            withdrawOpen.value = false;
            withdrawForm.reset();
        },
    });
}

const historyDot: Record<string, string> = {
    draft: 'bg-navy-300',
    new: 'bg-brand-500',
    reviewing: 'bg-sky-500',
    revision: 'bg-amber-500',
    accepted: 'bg-emerald-500',
    published: 'bg-violet-500',
    rejected: 'bg-red-500',
    withdrawn: 'bg-navy-400',
};
</script>

<template>
    <Head :title="article.title" />

    <div class="flex flex-col gap-5">
        <CabinetPageHeader
            :title="article.title"
            :icon="FileText"
            :quote="false"
            :breadcrumbs="[
                { title: 'Mening maqolalarim', href: index() },
                { title: 'Maqola', href: show(article.uuid) },
            ]"
        >
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <ArticleStatusPill
                    :group="article.statusGroup"
                    :label="article.statusLabel"
                />
                <span
                    v-if="article.updatedAt"
                    class="text-xs text-navy-500 tabular-nums"
                >
                    Oxirgi yangilanish: {{ formatDate(article.updatedAt) }}
                    {{ formatTime(article.updatedAt) }}
                </span>
            </div>
        </CabinetPageHeader>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_19rem]">
            <div class="flex min-w-0 flex-col gap-5">
                <RevisionCard
                    v-if="revision"
                    :revision="revision"
                    :has-reviews="reviews.length > 0"
                />

                <ArticleReviewsCard
                    v-if="reviews.length"
                    id="reviews"
                    class="scroll-mt-24"
                    :reviews="reviews"
                />

                <DashCard
                    v-if="messages.sendUrl || messages.items.length"
                    id="yozishma"
                    class="scroll-mt-24"
                >
                    <h2
                        class="mb-1 flex items-center gap-2 font-sans text-[15px] font-bold text-navy-950"
                    >
                        <MessagesSquare class="size-[18px] text-brand-600" />
                        Tahririyat bilan yozishma
                    </h2>
                    <p class="mb-4 text-xs text-navy-500">
                        Maqola bo'yicha savollaringizni shu yerda yozing — javob
                        email orqali ham xabar qilinadi.
                    </p>
                    <MessageThread
                        :messages="messages.items"
                        :send-url="messages.sendUrl"
                        empty-text="Tahririyat bilan yozishma hali boshlanmagan."
                    />
                </DashCard>

                <DashCard title="Maqola ma'lumotlari">
                    <dl
                        class="grid gap-x-6 gap-y-3 sm:grid-cols-2 2xl:grid-cols-3"
                    >
                        <div
                            v-for="item in details"
                            :key="item.label"
                            class="rounded-lg bg-[#f8fafd] px-3 py-2.5"
                        >
                            <dt class="text-[11px] font-medium text-navy-500">
                                {{ item.label }}
                            </dt>
                            <dd
                                class="mt-0.5 text-[13px] font-semibold break-words text-navy-900"
                            >
                                {{ item.value }}
                            </dd>
                        </div>
                    </dl>

                    <div v-if="article.abstract" class="mt-5">
                        <h3 class="text-[13px] font-bold text-navy-950">
                            Annotatsiya
                        </h3>
                        <p
                            class="mt-1.5 text-[13px] leading-relaxed whitespace-pre-line text-navy-700"
                        >
                            {{ article.abstract }}
                        </p>
                    </div>

                    <div v-if="article.keywords.length" class="mt-4">
                        <h3 class="text-[13px] font-bold text-navy-950">
                            Kalit so'zlar
                        </h3>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <span
                                v-for="keyword in article.keywords"
                                :key="keyword"
                                class="rounded-md bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700 ring-1 ring-brand-100 ring-inset"
                            >
                                {{ keyword }}
                            </span>
                        </div>
                    </div>
                </DashCard>

                <div class="grid gap-5 lg:grid-cols-2">
                    <DashCard>
                        <h2
                            class="mb-4 flex items-center gap-2 font-sans text-[15px] font-bold text-navy-950"
                        >
                            <UsersRound class="size-[18px] text-brand-600" />
                            Mualliflar
                        </h2>
                        <ul class="flex flex-col gap-2">
                            <li
                                v-for="author in article.authors"
                                :key="author.id"
                                class="rounded-lg border border-line px-3 py-2.5 transition-colors hover:border-brand-200"
                            >
                                <p
                                    class="flex items-center gap-1.5 text-[13px] font-semibold text-navy-900"
                                >
                                    {{ author.name }}
                                    <Star
                                        v-if="author.isCorresponding"
                                        class="size-3.5 fill-gold-400 text-gold-500"
                                        aria-label="Aloqa uchun mas'ul muallif"
                                    />
                                </p>
                                <p
                                    v-if="author.organization"
                                    class="mt-0.5 text-xs text-navy-600"
                                >
                                    {{ author.organization }}
                                </p>
                                <p
                                    v-if="author.email"
                                    class="mt-0.5 flex items-center gap-1 text-xs text-navy-500"
                                >
                                    <Mail class="size-3" />
                                    {{ author.email }}
                                </p>
                            </li>
                        </ul>
                    </DashCard>

                    <DashCard>
                        <h2
                            class="mb-4 flex items-center gap-2 font-sans text-[15px] font-bold text-navy-950"
                        >
                            <FileText class="size-[18px] text-brand-600" />
                            Fayllar
                        </h2>
                        <ul
                            v-if="article.files.length"
                            class="flex flex-col gap-2"
                        >
                            <li v-for="file in article.files" :key="file.id">
                                <a
                                    :href="file.url"
                                    class="group flex items-center gap-3 rounded-lg border border-line px-3 py-2.5 transition-all hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-[0_10px_20px_-14px_rgba(0,108,246,0.7)]"
                                >
                                    <span
                                        class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-[10px] font-bold text-brand-700 uppercase"
                                    >
                                        {{ file.extension }}
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span
                                            class="block truncate text-[13px] font-medium text-navy-900 group-hover:text-brand-700"
                                        >
                                            {{ file.name }}
                                        </span>
                                        <span
                                            class="block text-[11px] text-navy-500"
                                        >
                                            {{ file.typeLabel }} ·
                                            {{ formatFileSize(file.size) }}
                                        </span>
                                    </span>
                                    <Download
                                        class="size-4 text-navy-400 transition-all group-hover:translate-y-0.5 group-hover:text-brand-600"
                                    />
                                </a>
                            </li>
                        </ul>
                        <p
                            v-else
                            class="py-4 text-center text-sm text-navy-500"
                        >
                            Fayllar yuklanmagan
                        </p>
                    </DashCard>
                </div>

                <DashCard id="tarix" class="scroll-mt-24">
                    <h2
                        class="mb-4 flex items-center gap-2 font-sans text-[15px] font-bold text-navy-950"
                    >
                        <History class="size-[18px] text-brand-600" />
                        Holat tarixi
                    </h2>
                    <ol
                        v-if="article.history.length"
                        class="relative flex flex-col gap-4 border-l-2 border-line pl-5"
                    >
                        <li
                            v-for="item in article.history"
                            :key="item.id"
                            class="relative"
                        >
                            <span
                                :class="
                                    cn(
                                        'absolute top-1 -left-[27px] size-3 rounded-full ring-4 ring-white',
                                        historyDot[item.statusGroup],
                                    )
                                "
                                aria-hidden="true"
                            />
                            <div class="flex flex-wrap items-center gap-2">
                                <ArticleStatusPill
                                    :group="item.statusGroup"
                                    :label="item.statusLabel"
                                />
                                <span
                                    class="text-[11px] text-navy-400 tabular-nums"
                                >
                                    {{ formatDate(item.createdAt) }}
                                    {{ formatTime(item.createdAt) }}
                                </span>
                            </div>
                            <p
                                v-if="item.comment"
                                class="mt-1.5 rounded-lg bg-[#f8fafd] px-3 py-2 text-[13px] leading-relaxed whitespace-pre-line text-navy-700"
                            >
                                {{ item.comment }}
                            </p>
                        </li>
                    </ol>
                    <p v-else class="py-4 text-center text-sm text-navy-500">
                        Tarix hali yo'q
                    </p>
                </DashCard>
            </div>

            <aside
                class="grid content-start gap-5 md:grid-cols-2 xl:grid-cols-1"
            >
                <DashCard>
                    <h2
                        class="mb-4 flex items-center gap-2 font-sans text-[15px] font-bold text-navy-950"
                    >
                        <Route class="size-[18px] text-brand-600" />
                        Maqolaning holati
                    </h2>
                    <StatusTimeline :steps="steps" />
                </DashCard>

                <ArticlePaymentCard v-if="payment" :payment="payment" />

                <DashCard title="Amallar">
                    <div class="flex flex-col gap-2">
                        <a
                            v-if="article.publicUrl"
                            :href="article.publicUrl"
                            target="_blank"
                            rel="noopener"
                            class="group flex items-center gap-3 rounded-lg bg-brand-600 px-3.5 py-2.5 text-[13px] font-semibold text-white transition-all hover:-translate-y-0.5 hover:bg-brand-700"
                        >
                            <ExternalLink class="size-4" />
                            Saytda ko'rish
                        </a>
                        <Link
                            v-if="article.can.edit && article.editUrl"
                            :href="article.editUrl"
                            class="group flex items-center gap-3 rounded-lg bg-brand-600 px-3.5 py-2.5 text-[13px] font-semibold text-white transition-all hover:-translate-y-0.5 hover:bg-brand-700"
                        >
                            <PenLine
                                class="size-4 transition-transform group-hover:-rotate-12"
                            />
                            Formani davom ettirish
                        </Link>
                        <button
                            v-if="article.can.delete && article.destroyUrl"
                            type="button"
                            class="group flex items-center gap-3 rounded-lg border border-red-200 bg-white px-3.5 py-2.5 text-[13px] font-medium text-red-700 transition-all hover:-translate-y-0.5 hover:bg-red-50"
                            @click="deleteOpen = true"
                        >
                            <Trash2
                                class="size-4 transition-transform group-hover:-rotate-12"
                            />
                            Qoralamani o'chirish
                        </button>
                        <button
                            v-if="article.can.withdraw && !article.can.delete"
                            type="button"
                            class="group flex items-center gap-3 rounded-lg border border-red-200 bg-white px-3.5 py-2.5 text-[13px] font-medium text-red-700 transition-all hover:-translate-y-0.5 hover:bg-red-50"
                            @click="withdrawOpen = true"
                        >
                            <Undo2
                                class="size-4 transition-transform group-hover:-rotate-12"
                            />
                            Maqolani qaytarib olish
                        </button>
                        <Link
                            :href="index()"
                            class="group flex items-center gap-3 rounded-lg border border-line px-3.5 py-2.5 text-[13px] font-medium text-navy-700 transition-all hover:-translate-y-0.5 hover:border-brand-200 hover:text-brand-700"
                        >
                            <ArrowLeft
                                class="size-4 transition-transform group-hover:-translate-x-0.5"
                            />
                            Barcha maqolalar
                        </Link>
                    </div>
                </DashCard>
            </aside>
        </div>
    </div>

    <ActionDialog
        v-model:open="deleteOpen"
        title="Qoralamani o'chirasizmi?"
        description="Kiritilgan barcha ma'lumotlar va yuklangan fayllar butunlay o'chiriladi."
        :icon="Trash2"
        tone="danger"
        confirm-text="O'chirish"
        :processing="deleting"
        @confirm="destroyDraft"
    />

    <ActionDialog
        v-model:open="withdrawOpen"
        title="Maqolani qaytarib olasizmi?"
        description="Qaytarib olingan maqola tahririyat tomonidan ko'rib chiqilmaydi. Bu amalni bekor qilib bo'lmaydi."
        :icon="Undo2"
        tone="danger"
        confirm-text="Qaytarib olish"
        :processing="withdrawForm.processing"
        @confirm="submitWithdraw"
    >
        <label class="block text-xs font-medium text-navy-700" for="reason">
            Sababi (ixtiyoriy)
        </label>
        <textarea
            id="reason"
            v-model="withdrawForm.reason"
            rows="3"
            maxlength="1000"
            :class="cn(textareaClass, 'mt-1')"
        />
        <InputError :message="withdrawForm.errors.reason" />
        <InputError
            :message="(withdrawForm.errors as Record<string, string>).status"
        />
    </ActionDialog>
</template>
