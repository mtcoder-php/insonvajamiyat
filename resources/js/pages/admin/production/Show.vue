<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BadgeCheck,
    BookOpen,
    Building2,
    CalendarDays,
    Download,
    ExternalLink,
    Eye,
    FileText,
    FileUp,
    FolderOpen,
    LoaderCircle,
    MessageSquareWarning,
    PenLine,
    Star,
    UserRound,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import EditorialNotes from '@/components/admin/articles/EditorialNotes.vue';
import FinalActions from '@/components/admin/production/FinalActions.vue';
import MetadataDialog from '@/components/admin/production/MetadataDialog.vue';
import ProductionChecklist from '@/components/admin/production/ProductionChecklist.vue';
import ProductionStepper from '@/components/admin/production/ProductionStepper.vue';
import ArticleStatusPill from '@/components/cabinet/ArticleStatusPill.vue';
import { formatDate, formatFileSize } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/production';
import type { ProductionFile, ProductionShowProps } from '@/types';

/**
 * Nashr jarayoni — maqola kartasi (admin publisher page.png).
 */
const props = defineProps<ProductionShowProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin panel', href: dashboard() },
            { title: 'Nashr jarayoni', href: index() },
            { title: 'Maqola', href: index() },
        ],
    },
});

const metadataOpen = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const uploading = ref(false);
const uploadError = ref<string | null>(null);
const dragging = ref(false);

function upload(file: File | null | undefined): void {
    if (!file) {
        return;
    }

    uploadError.value = null;
    uploading.value = true;
    router.post(
        props.article.urls.upload,
        { pdf: file },
        {
            preserveScroll: true,
            forceFormData: true,
            onError: (errors) => (uploadError.value = errors.pdf ?? null),
            onFinish: () => {
                uploading.value = false;

                if (fileInput.value) {
                    fileInput.value.value = '';
                }
            },
        },
    );
}

const fileTint = (file: ProductionFile): string =>
    file.type === 'final_pdf'
        ? 'bg-emerald-50 text-emerald-700'
        : file.extension === 'pdf'
          ? 'bg-red-50 text-red-600'
          : ['png', 'jpg', 'jpeg'].includes(file.extension)
            ? 'bg-amber-50 text-amber-600'
            : 'bg-brand-50 text-brand-700';

const statusBox = computed(() => {
    const a = props.article;

    if (a.status === 'published') {
        return {
            title: 'Nashr etilgan',
            text: 'Maqola saytda chop etilgan.',
            tone: 'emerald',
        };
    }

    if (a.production.approvedAt) {
        return {
            title: 'Nashrga tayyor',
            text: "Maqola tahrirdan o'tgan va bosh muharrir tasdiqlagan.",
            tone: 'emerald',
        };
    }

    if (a.status === 'accepted') {
        return {
            title: 'Maketga olinmagan',
            text: '«Maketga olish» tugmasi bilan jarayonni boshlang.',
            tone: 'amber',
        };
    }

    return {
        title: 'Maketlanmoqda',
        text: `${a.checks.filter((c) => c.ok).length}/${a.checks.length} tekshiruv bajarilgan.`,
        tone: 'brand',
    };
});

const boxTint: Record<string, string> = {
    emerald: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    amber: 'border-amber-200 bg-amber-50 text-amber-800',
    brand: 'border-brand-200 bg-brand-50 text-brand-800',
};

const info = computed(() =>
    [
        { label: 'Maqola ID', value: `#IJ-${props.article.code}` },
        { label: 'Turi', value: props.article.type },
        { label: "Yo'nalish", value: props.article.subject },
        { label: 'UDK', value: props.article.udc },
        { label: 'DOI', value: props.article.doi },
        {
            label: 'Jild / Son',
            value: props.article.issue
                ? `${props.article.issue.volume ?? '—'} / ${props.article.issue.number} (${props.article.issue.year})`
                : null,
        },
        {
            label: 'Sahifa',
            value:
                props.article.issue?.pageFrom && props.article.issue.pageTo
                    ? `${props.article.issue.pageFrom}–${props.article.issue.pageTo}`
                    : null,
        },
        {
            label: 'Plagiat',
            value:
                props.article.plagiarism !== null
                    ? `${props.article.plagiarism}%`
                    : null,
        },
        { label: 'Maketchi', value: props.article.production.layoutEditor },
    ].map((row) => ({ ...row, value: row.value ?? '—' })),
);
</script>

<template>
    <Head :title="`Nashr — ${article.title}`" />

    <div class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 md:p-6">
        <!-- Sarlavha va bosqichlar -->
        <section
            class="rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start">
                <span
                    class="hidden size-20 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-navy-900 to-brand-700 text-white shadow-[0_14px_30px_-16px_rgba(0,36,66,0.8)] sm:flex"
                >
                    <BookOpen class="size-9" />
                </span>
                <div class="min-w-0 flex-1">
                    <Link
                        :href="article.urls.index"
                        class="mb-2 inline-flex items-center gap-1 text-xs font-semibold text-navy-500 transition-colors hover:text-brand-700"
                    >
                        <ArrowLeft class="size-3.5" /> Nashr jarayoni
                    </Link>
                    <h1
                        class="font-serif text-xl leading-snug font-bold text-navy-950 md:text-2xl"
                    >
                        {{ article.title }}
                    </h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span
                            v-if="article.doi"
                            class="inline-flex items-center gap-1.5 rounded-md bg-amber-50 px-2 py-0.5 font-mono text-[11px] text-amber-800 ring-1 ring-amber-200 ring-inset"
                        >
                            <b class="font-sans">DOI</b> {{ article.doi }}
                        </span>
                        <ArticleStatusPill
                            :group="article.statusGroup"
                            :label="article.statusLabel"
                        />
                    </div>
                    <div
                        class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-[13px] text-navy-700"
                    >
                        <span class="inline-flex items-center gap-1.5">
                            <UserRound class="size-4 text-navy-400" />
                            {{ article.submitter }}
                        </span>
                        <span
                            v-if="article.issue"
                            class="inline-flex items-center gap-1.5"
                        >
                            <BookOpen class="size-4 text-navy-400" />
                            Jurnal soni: {{ article.issue.label }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <CalendarDays class="size-4 text-navy-400" />
                            Qabul: {{ formatDate(article.acceptedAt) }}
                        </span>
                    </div>
                </div>
                <div
                    :class="
                        cn(
                            'flex shrink-0 items-start gap-3 rounded-xl border p-4 lg:w-64',
                            boxTint[statusBox.tone],
                        )
                    "
                >
                    <BadgeCheck class="mt-0.5 size-5 shrink-0" />
                    <span>
                        <b class="block text-sm">{{ statusBox.title }}</b>
                        <span class="text-xs opacity-80">{{
                            statusBox.text
                        }}</span>
                    </span>
                </div>
            </div>
            <div class="mt-5 border-t border-line pt-5">
                <ProductionStepper :steps="article.steps" />
            </div>
        </section>

        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
            <div class="grid min-w-0 gap-5">
                <div class="grid items-start gap-5 lg:grid-cols-2">
                    <!-- Maqola ma'lumotlari -->
                    <section
                        class="rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-shadow duration-300 hover:shadow-[0_12px_32px_-18px_rgba(0,36,66,0.25)]"
                    >
                        <header
                            class="mb-4 flex items-center justify-between gap-3"
                        >
                            <h2
                                class="flex items-center gap-2 font-sans text-[15px] font-bold text-navy-950"
                            >
                                <FileText class="size-[18px] text-brand-600" />
                                Maqola ma'lumotlari
                            </h2>
                            <button
                                v-if="article.can.edit"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-line px-2.5 py-1.5 text-xs font-semibold text-brand-700 transition-all hover:-translate-y-px hover:border-brand-300 hover:bg-brand-50"
                                @click="metadataOpen = true"
                            >
                                <PenLine class="size-3.5" /> Tahrirlash
                            </button>
                        </header>
                        <dl class="grid gap-2.5 text-[13px]">
                            <div
                                v-for="row in info"
                                :key="row.label"
                                class="grid grid-cols-[7rem_minmax(0,1fr)] gap-2"
                            >
                                <dt class="text-navy-500">{{ row.label }}</dt>
                                <dd
                                    class="font-medium break-words text-navy-900"
                                >
                                    {{ row.value }}
                                </dd>
                            </div>
                        </dl>
                        <div class="mt-4 border-t border-line pt-4">
                            <p class="mb-2 text-xs font-semibold text-navy-700">
                                Mualliflar
                            </p>
                            <ul class="grid gap-1.5">
                                <li
                                    v-for="author in article.authors"
                                    :key="author.name"
                                    class="text-[13px]"
                                >
                                    <span
                                        class="flex items-center gap-1 font-semibold text-navy-900"
                                    >
                                        {{ author.name }}
                                        <Star
                                            v-if="author.isCorresponding"
                                            class="size-3.5 fill-gold-400 text-gold-500"
                                        />
                                    </span>
                                    <span
                                        v-if="author.organization"
                                        class="flex items-center gap-1 text-xs text-navy-500"
                                    >
                                        <Building2 class="size-3" />
                                        {{ author.organization }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                        <div
                            v-if="article.keywords.length"
                            class="mt-4 flex flex-wrap gap-1.5"
                        >
                            <span
                                v-for="word in article.keywords"
                                :key="word"
                                class="rounded-md border border-brand-100 bg-brand-50/60 px-2 py-0.5 text-[11px] font-medium text-brand-800"
                                >{{ word }}</span
                            >
                        </div>
                        <p
                            v-if="article.abstract"
                            class="mt-4 line-clamp-6 text-xs leading-relaxed text-navy-600"
                        >
                            {{ article.abstract }}
                        </p>
                    </section>

                    <!-- Hujjatlar va fayllar -->
                    <section
                        class="rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-shadow duration-300 hover:shadow-[0_12px_32px_-18px_rgba(0,36,66,0.25)]"
                    >
                        <h2
                            class="mb-4 flex items-center gap-2 font-sans text-[15px] font-bold text-navy-950"
                        >
                            <FolderOpen class="size-[18px] text-brand-600" />
                            Hujjatlar va fayllar
                        </h2>
                        <ul class="grid gap-2">
                            <li
                                v-for="file in article.files"
                                :key="file.uuid"
                                class="flex items-center gap-3 rounded-lg border border-line px-3 py-2 transition-all hover:-translate-y-px hover:border-brand-200 hover:shadow-sm"
                            >
                                <span
                                    :class="
                                        cn(
                                            'flex size-9 shrink-0 items-center justify-center rounded-lg text-[10px] font-bold uppercase',
                                            fileTint(file),
                                        )
                                    "
                                    >{{ file.extension }}</span
                                >
                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block truncate text-[13px] font-semibold text-navy-900"
                                        >{{ file.name }}</span
                                    >
                                    <span class="text-[11px] text-navy-500"
                                        >{{ file.typeLabel }} ·
                                        {{ formatFileSize(file.size) }}</span
                                    >
                                </span>
                                <a
                                    v-if="file.viewUrl"
                                    :href="file.viewUrl"
                                    target="_blank"
                                    rel="noopener"
                                    class="rounded-md p-1.5 text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700"
                                    title="Ko'rish"
                                >
                                    <Eye class="size-4" />
                                </a>
                                <a
                                    :href="file.downloadUrl"
                                    class="rounded-md p-1.5 text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700"
                                    title="Yuklab olish"
                                >
                                    <Download class="size-4" />
                                </a>
                            </li>
                        </ul>

                        <button
                            v-if="article.can.manage"
                            type="button"
                            :disabled="uploading"
                            :class="
                                cn(
                                    'mt-3 flex w-full flex-col items-center gap-1.5 rounded-xl border-2 border-dashed px-4 py-5 text-center transition-all disabled:opacity-60',
                                    dragging
                                        ? 'border-brand-400 bg-brand-50'
                                        : 'border-navy-200 hover:border-brand-300 hover:bg-brand-50/40',
                                )
                            "
                            @click="fileInput?.click()"
                            @dragover.prevent="dragging = true"
                            @dragleave.prevent="dragging = false"
                            @drop.prevent="
                                dragging = false;
                                upload($event.dataTransfer?.files[0]);
                            "
                        >
                            <LoaderCircle
                                v-if="uploading"
                                class="size-6 animate-spin text-brand-500"
                            />
                            <FileUp v-else class="size-6 text-brand-500" />
                            <span class="text-[13px] text-navy-700">
                                <b class="text-brand-700">Yakuniy PDF</b> ni
                                tanlang yoki shu yerga tashlang
                            </span>
                            <span class="text-[11px] text-navy-500"
                                >Faqat .pdf, 30 MB gacha. Muallifga korrektura
                                uchun yuboriladi.</span
                            >
                        </button>
                        <p v-if="uploadError" class="mt-1 text-xs text-red-600">
                            {{ uploadError }}
                        </p>
                        <input
                            ref="fileInput"
                            type="file"
                            accept="application/pdf,.pdf"
                            class="hidden"
                            @change="
                                upload(
                                    ($event.target as HTMLInputElement)
                                        .files?.[0],
                                )
                            "
                        />

                        <div
                            v-if="article.production.authorChanges"
                            class="mt-4 rounded-lg border-l-4 border-orange-400 bg-orange-50/70 px-3 py-2.5"
                        >
                            <p
                                class="flex items-center gap-1.5 text-xs font-semibold text-orange-800"
                            >
                                <MessageSquareWarning class="size-4" />
                                Muallif tuzatish so'ragan ·
                                {{
                                    formatDate(
                                        article.production.authorChangesAt,
                                    )
                                }}
                            </p>
                            <p
                                class="mt-1 text-[13px] leading-relaxed whitespace-pre-line text-navy-800"
                            >
                                {{ article.production.authorChanges }}
                            </p>
                        </div>
                        <p
                            v-else-if="article.production.authorApproved"
                            class="mt-4 flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800"
                        >
                            <BadgeCheck class="size-4" />
                            Muallif korrekturani tasdiqlagan ·
                            {{
                                formatDate(article.production.authorApprovedAt)
                            }}
                        </p>
                    </section>
                </div>

                <ProductionChecklist
                    v-if="article.status !== 'accepted'"
                    :article="article"
                />

                <section
                    class="rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                >
                    <EditorialNotes
                        :notes="article.notes"
                        :url="article.urls.notes"
                        reload="article"
                        title="Tahririyat izohlari"
                    />
                </section>
            </div>

            <!-- O'ng ustun -->
            <aside class="grid content-start gap-5">
                <section
                    class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                >
                    <header
                        class="flex items-center gap-2 border-b border-line px-4 py-3"
                    >
                        <h2
                            class="flex-1 font-sans text-[15px] font-bold text-navy-950"
                        >
                            Maqola PDF preview
                        </h2>
                        <template v-if="article.preview">
                            <a
                                :href="article.preview.downloadUrl"
                                class="rounded-md p-1.5 text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700"
                                title="Yuklab olish"
                            >
                                <Download class="size-4" />
                            </a>
                            <a
                                :href="article.preview.url"
                                target="_blank"
                                rel="noopener"
                                class="rounded-md p-1.5 text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700"
                                title="To'liq ekran"
                            >
                                <ExternalLink class="size-4" />
                            </a>
                        </template>
                    </header>
                    <template v-if="article.preview">
                        <p
                            :class="
                                cn(
                                    'px-4 py-1.5 text-[11px] font-semibold',
                                    article.preview.isFinal
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : 'bg-amber-50 text-amber-700',
                                )
                            "
                        >
                            {{
                                article.preview.isFinal
                                    ? 'Yakuniy PDF'
                                    : "Qo'lyozma (yakuniy PDF hali yo'q)"
                            }}
                            · {{ article.preview.name }}
                        </p>
                        <iframe
                            :src="article.preview.url"
                            :title="article.preview.name"
                            class="h-[560px] w-full bg-[#eef2f8]"
                        />
                    </template>
                    <div
                        v-else
                        class="flex h-60 flex-col items-center justify-center gap-2 px-6 text-center text-sm text-navy-500"
                    >
                        <FileText class="size-8 text-navy-300" />
                        PDF fayl yo'q. Yakuniy PDF yuklangach shu yerda
                        ko'rinadi.
                    </div>
                </section>

                <section
                    class="rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                >
                    <h2
                        class="mb-4 font-sans text-[15px] font-bold text-navy-950"
                    >
                        Jurnal soni
                    </h2>
                    <div v-if="article.issue" class="flex gap-4">
                        <span
                            class="flex h-24 w-[4.5rem] shrink-0 flex-col items-center justify-center rounded-md bg-gradient-to-b from-navy-900 to-brand-800 text-white shadow-[0_10px_24px_-14px_rgba(0,36,66,0.9)]"
                        >
                            <span class="text-[9px] font-semibold opacity-70"
                                >INSON VA JAMIYAT</span
                            >
                            <span class="mt-1 text-lg font-bold"
                                >№{{ article.issue.number }}</span
                            >
                            <span class="text-[10px] opacity-80">{{
                                article.issue.year
                            }}</span>
                        </span>
                        <dl class="grid flex-1 gap-1.5 text-[13px]">
                            <div class="flex justify-between gap-2">
                                <dt class="text-navy-500">Son</dt>
                                <dd class="font-semibold text-navy-900">
                                    {{ article.issue.label }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-navy-500">Maqolalar</dt>
                                <dd class="font-semibold text-navy-900">
                                    {{ article.issue.articlesCount }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-navy-500">Nashr sanasi</dt>
                                <dd class="font-semibold text-navy-900">
                                    {{
                                        article.issue.publishedAt
                                            ? formatDate(
                                                  article.issue.publishedAt,
                                              )
                                            : '—'
                                    }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-navy-500">Holat</dt>
                                <dd>
                                    <span
                                        :class="
                                            cn(
                                                'rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                                article.issue.status ===
                                                    'published'
                                                    ? 'bg-emerald-50 text-emerald-700'
                                                    : 'bg-amber-50 text-amber-700',
                                            )
                                        "
                                        >{{ article.issue.statusLabel }}</span
                                    >
                                </dd>
                            </div>
                        </dl>
                    </div>
                    <div
                        v-else
                        class="rounded-lg bg-[#f5f8fc] p-4 text-center text-[13px] text-navy-600"
                    >
                        Maqola hali jurnal soniga biriktirilmagan.
                        <button
                            v-if="article.can.edit"
                            type="button"
                            class="mt-2 block w-full rounded-lg border border-brand-200 px-3 py-2 text-xs font-semibold text-brand-700 transition-colors hover:bg-brand-50"
                            @click="metadataOpen = true"
                        >
                            Songa biriktirish
                        </button>
                    </div>
                </section>

                <FinalActions :article="article" @upload="fileInput?.click()" />
            </aside>
        </div>
    </div>

    <MetadataDialog
        v-model:open="metadataOpen"
        :article="article"
        :issues="issues"
    />
</template>
