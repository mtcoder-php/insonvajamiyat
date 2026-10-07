<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BadgeCheck,
    BookOpen,
    Calculator,
    CircleCheck,
    CircleDashed,
    Download,
    ExternalLink,
    FilePlus2,
    FileText,
    ImageUp,
    ListOrdered,
    LoaderCircle,
    PenLine,
    Printer,
    Send,
    Trash2,
    TriangleAlert,
    Upload,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import AddArticlesDialog from '@/components/admin/issues/AddArticlesDialog.vue';
import IssueArticlesTable from '@/components/admin/issues/IssueArticlesTable.vue';
import IssueFormDialog from '@/components/admin/issues/IssueFormDialog.vue';
import IssuePdfBuildCard from '@/components/admin/issues/IssuePdfBuildCard.vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import { inputClass, secondaryButtonClass } from '@/lib/formStyles';
import { formatDate, formatFileSize } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/issues';
import type { IssueShowProps } from '@/types';

/**
 * Jurnal soni: tarkib (maqolalar tartibi, rukn, sahifalar), muqova, PDF, mundarija.
 */
const props = defineProps<IssueShowProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin panel', href: dashboard() },
            { title: 'Jurnallar', href: index() },
            { title: 'Son', href: index() },
        ],
    },
});

const editOpen = ref(false);
const publishOpen = ref(false);
const publishing = ref(false);

function publish(): void {
    publishing.value = true;
    router.post(
        props.issue.urls.publish,
        {},
        {
            preserveScroll: true,
            onSuccess: () => (publishOpen.value = false),
            onFinish: () => (publishing.value = false),
        },
    );
}
const addOpen = ref(false);
const deleteOpen = ref(false);
const deleting = ref(false);

// Fayllar: muqova, to'liq son PDF, mundarija PDF
type FileType = 'cover' | 'pdf' | 'toc';
const uploading = ref<FileType | null>(null);
const fileErrors = ref<Partial<Record<FileType, string>>>({});
const inputs = ref<Partial<Record<FileType, HTMLInputElement | null>>>({});

function pick(type: FileType): void {
    inputs.value[type]?.click();
}

function upload(type: FileType, event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        return;
    }

    uploading.value = type;
    fileErrors.value = { ...fileErrors.value, [type]: undefined };
    router.post(
        props.issue.urls.files,
        { type, file },
        {
            preserveScroll: true,
            forceFormData: true,
            onError: (errors) =>
                (fileErrors.value = {
                    ...fileErrors.value,
                    [type]: errors.file ?? errors.type,
                }),
            onFinish: () => {
                uploading.value = null;
                input.value = '';
            },
        },
    );
}

function removeFile(type: FileType): void {
    router.delete(props.issue.urls.fileDestroy.replace('__type__', type), {
        preserveScroll: true,
    });
}

const files = computed(() => [
    {
        type: 'pdf' as const,
        title: "To'liq son PDF",
        hint: 'Muqova, mundarija va barcha maqolalar bitta faylda — saytda «Sonni yuklab olish».',
        info: props.issue.files.pdf,
    },
    {
        type: 'toc' as const,
        title: 'Mundarija PDF',
        hint: '«Mundarijani chop etish» sahifasidan PDF sifatida saqlab yuklang.',
        info: props.issue.files.toc,
    },
]);

// Sahifalarni avtomatik hisoblash
const paginateOpen = ref(false);
const paginateForm = useForm({ start_page: 1 });

function paginate(): void {
    paginateForm.post(props.issue.urls.paginate, {
        preserveScroll: true,
        onSuccess: () => (paginateOpen.value = false),
    });
}

function destroy(): void {
    deleting.value = true;
    router.delete(props.issue.urls.destroy, {
        onFinish: () => (deleting.value = false),
    });
}

const checks = computed(() => [
    {
        ok: props.issue.summary.total > 0,
        label: `Maqolalar: ${props.issue.summary.total} ta`,
    },
    {
        ok:
            props.issue.summary.total > 0 &&
            props.issue.summary.ready === props.issue.summary.total,
        label: `Nashrga tayyor: ${props.issue.summary.ready}/${props.issue.summary.total}`,
    },
    {
        ok:
            props.issue.summary.total > 0 &&
            props.issue.summary.withPages === props.issue.summary.total,
        label: `Sahifalar belgilangan: ${props.issue.summary.withPages}/${props.issue.summary.total}`,
    },
    { ok: props.issue.hasOwnCover, label: 'Muqova yuklangan' },
    { ok: props.issue.files.pdf !== null, label: "To'liq son PDF" },
]);
</script>

<template>
    <Head :title="`Jurnal soni ${issue.label}`" />

    <div class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 md:p-6">
        <!-- Sarlavha -->
        <section
            class="rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <div class="flex flex-col gap-5 sm:flex-row">
                <div class="group relative h-44 w-32 shrink-0 self-start">
                    <span
                        class="block size-full overflow-hidden rounded-lg bg-gradient-to-b from-navy-900 to-brand-800 shadow-[0_16px_34px_-18px_rgba(0,36,66,0.9)]"
                    >
                        <img
                            v-if="issue.coverUrl"
                            :src="issue.coverUrl"
                            :alt="issue.label"
                            class="size-full object-cover"
                        />
                        <span
                            v-else
                            class="flex size-full flex-col items-center justify-center text-white"
                        >
                            <BookOpen class="size-8 opacity-70" />
                            <span class="mt-1 text-xl font-bold"
                                >№{{ issue.number }}</span
                            >
                        </span>
                    </span>
                    <button
                        v-if="issue.can.manage"
                        type="button"
                        class="absolute inset-0 flex flex-col items-center justify-center gap-1 rounded-lg bg-navy-950/70 text-xs font-semibold text-white opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100"
                        @click="pick('cover')"
                    >
                        <LoaderCircle
                            v-if="uploading === 'cover'"
                            class="size-6 animate-spin"
                        />
                        <ImageUp v-else class="size-6" />
                        {{
                            issue.hasOwnCover
                                ? 'Muqovani almashtirish'
                                : 'Muqova yuklash'
                        }}
                    </button>
                    <input
                        :ref="(el) => (inputs.cover = el as HTMLInputElement)"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="hidden"
                        @change="upload('cover', $event)"
                    />
                </div>

                <div class="min-w-0 flex-1">
                    <Link
                        :href="issue.urls.index"
                        class="mb-2 inline-flex items-center gap-1 text-xs font-semibold text-navy-500 transition-colors hover:text-brand-700"
                    >
                        <ArrowLeft class="size-3.5" /> Jurnallar
                    </Link>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1
                            class="font-serif text-2xl font-bold text-navy-950 md:text-3xl"
                        >
                            {{ issue.year }}-yil, {{ issue.number }}-son
                        </h1>
                        <span
                            :class="
                                cn(
                                    'rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset',
                                    issue.status === 'published'
                                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                        : 'bg-amber-50 text-amber-700 ring-amber-200',
                                )
                            "
                            >{{ issue.statusLabel }}</span
                        >
                    </div>
                    <p
                        v-if="issue.title"
                        class="mt-1 text-sm font-medium text-navy-700"
                    >
                        {{ issue.title }}
                    </p>
                    <dl
                        class="mt-3 flex flex-wrap gap-x-6 gap-y-1.5 text-[13px] text-navy-600"
                    >
                        <div v-if="issue.volume">
                            <dt class="me-1 inline text-navy-400">Jild:</dt>
                            <dd class="inline font-semibold text-navy-800">
                                {{ issue.volume }}
                            </dd>
                        </div>
                        <div>
                            <dt class="me-1 inline text-navy-400">DOI:</dt>
                            <dd
                                class="inline font-mono text-xs font-semibold text-navy-800"
                            >
                                {{ issue.doi ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="me-1 inline text-navy-400">Hajm:</dt>
                            <dd class="inline font-semibold text-navy-800">
                                {{ issue.summary.pages || '—' }} bet
                            </dd>
                        </div>
                        <div v-if="issue.publishedAt">
                            <dt class="me-1 inline text-navy-400">
                                Chop etilgan:
                            </dt>
                            <dd class="inline font-semibold text-navy-800">
                                {{ formatDate(issue.publishedAt) }}
                            </dd>
                        </div>
                    </dl>
                    <p
                        v-if="issue.description"
                        class="mt-2 line-clamp-3 max-w-3xl text-xs leading-relaxed text-navy-500"
                    >
                        {{ issue.description }}
                    </p>
                    <p
                        v-if="fileErrors.cover"
                        class="mt-2 text-xs text-red-600"
                    >
                        {{ fileErrors.cover }}
                    </p>
                </div>

                <div
                    v-if="issue.can.manage"
                    class="flex shrink-0 flex-wrap items-start gap-2 sm:flex-col sm:items-stretch"
                >
                    <button
                        type="button"
                        :class="cn(secondaryButtonClass, 'h-9 text-[13px]')"
                        @click="editOpen = true"
                    >
                        <PenLine class="size-4" /> Tahrirlash
                    </button>
                    <a
                        :href="issue.urls.toc"
                        target="_blank"
                        rel="noopener"
                        :class="cn(secondaryButtonClass, 'h-9 text-[13px]')"
                    >
                        <Printer class="size-4" /> Mundarijani chop etish
                    </a>
                    <button
                        v-if="issue.hasOwnCover"
                        type="button"
                        :class="
                            cn(
                                secondaryButtonClass,
                                'h-9 text-[13px] hover:border-red-300 hover:text-red-700',
                            )
                        "
                        @click="removeFile('cover')"
                    >
                        <ImageUp class="size-4" /> Muqovani olib tashlash
                    </button>
                    <button
                        v-if="issue.can.delete"
                        type="button"
                        :class="
                            cn(
                                secondaryButtonClass,
                                'h-9 text-[13px] hover:border-red-300 hover:text-red-700',
                            )
                        "
                        @click="deleteOpen = true"
                    >
                        <Trash2 class="size-4" /> Sonni o'chirish
                    </button>
                </div>
            </div>
        </section>

        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
            <!-- Tarkib -->
            <section
                class="min-w-0 rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
            >
                <header
                    class="mb-4 flex flex-wrap items-center justify-between gap-3"
                >
                    <h2
                        class="flex items-center gap-2 font-sans text-[15px] font-bold text-navy-950"
                    >
                        <ListOrdered class="size-[18px] text-brand-600" />
                        Son tarkibi
                        <span class="text-xs font-medium text-navy-400"
                            >— sudrab tartiblang</span
                        >
                    </h2>
                    <div v-if="issue.can.manage" class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            :disabled="!issue.articles.length"
                            :class="cn(secondaryButtonClass, 'h-9 text-[13px]')"
                            @click="paginateOpen = true"
                        >
                            <Calculator class="size-4" /> Sahifalarni hisoblash
                        </button>
                        <button
                            type="button"
                            class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-600 px-3.5 text-[13px] font-semibold text-white shadow-[0_8px_20px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500"
                            @click="addOpen = true"
                        >
                            <FilePlus2 class="size-4" /> Maqola qo'shish
                            <span
                                v-if="available.length"
                                class="rounded-full bg-white/20 px-1.5 text-[10px] tabular-nums"
                                >{{ available.length }}</span
                            >
                        </button>
                    </div>
                </header>
                <IssueArticlesTable :issue="issue" />
            </section>

            <aside class="grid min-w-0 content-start gap-5">
                <!-- Tayyorlik -->
                <section
                    class="rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                >
                    <h2
                        class="mb-3 font-sans text-[15px] font-bold text-navy-950"
                    >
                        Chop etishga tayyorlik
                    </h2>
                    <ul class="grid gap-2">
                        <li
                            v-for="check in checks"
                            :key="check.label"
                            class="flex items-center gap-2 text-[13px]"
                        >
                            <CircleCheck
                                v-if="check.ok"
                                class="size-[18px] shrink-0 text-emerald-500"
                            />
                            <CircleDashed
                                v-else
                                class="size-[18px] shrink-0 text-navy-300"
                            />
                            <span
                                :class="
                                    check.ok ? 'text-navy-900' : 'text-navy-500'
                                "
                                >{{ check.label }}</span
                            >
                        </li>
                    </ul>
                    <div
                        v-if="issue.summary.complete"
                        class="mt-4 flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800"
                    >
                        <BadgeCheck class="size-4" /> Barcha maqolalar nashrga
                        tayyor
                    </div>
                    <a
                        v-if="issue.publicUrl"
                        :href="issue.publicUrl"
                        target="_blank"
                        rel="noopener"
                        class="mt-4 flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 text-sm font-semibold text-white shadow-[0_10px_22px_-12px_rgba(5,150,105,0.9)] transition-all hover:-translate-y-px hover:bg-emerald-500"
                    >
                        <ExternalLink class="size-4" /> Saytda ko'rish
                    </a>
                    <template v-else>
                        <ul
                            v-if="issue.problems.length"
                            class="mt-4 grid gap-1 rounded-lg bg-amber-50 px-3 py-2.5 text-xs text-amber-900"
                        >
                            <li
                                v-for="problem in issue.problems.slice(0, 5)"
                                :key="problem"
                                class="flex gap-1.5"
                            >
                                <TriangleAlert
                                    class="mt-px size-3.5 shrink-0 text-amber-500"
                                />
                                {{ problem }}
                            </li>
                            <li
                                v-if="issue.problems.length > 5"
                                class="pl-5 text-amber-700"
                            >
                                va yana {{ issue.problems.length - 5 }} ta...
                            </li>
                        </ul>
                        <button
                            type="button"
                            :disabled="!issue.can.publish"
                            :title="
                                issue.can.publish
                                    ? undefined
                                    : 'Avval yuqoridagi kamchiliklarni bartaraf eting (chop etish — bosh muharrir huquqi)'
                            "
                            class="mt-4 flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-navy-900 text-sm font-semibold text-white shadow-[0_10px_22px_-12px_rgba(0,30,60,0.9)] transition-all hover:-translate-y-px hover:bg-brand-700 disabled:pointer-events-none disabled:opacity-50"
                            @click="publishOpen = true"
                        >
                            <Send class="size-4" /> Sonni chop etish
                        </button>
                    </template>
                </section>

                <!-- Fayllar -->
                <IssuePdfBuildCard
                    :build="issue.pdfBuild"
                    :file="issue.files.pdf"
                    :can-manage="issue.can.manage"
                />

                <section
                    class="rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                >
                    <h2
                        class="mb-3 font-sans text-[15px] font-bold text-navy-950"
                    >
                        Son fayllari
                    </h2>
                    <div class="grid gap-3">
                        <div
                            v-for="item in files"
                            :key="item.type"
                            class="rounded-lg border border-line p-3 transition-colors hover:border-brand-200"
                        >
                            <div class="flex items-start gap-3">
                                <span
                                    :class="
                                        cn(
                                            'flex size-9 shrink-0 items-center justify-center rounded-lg',
                                            item.info
                                                ? 'bg-red-50 text-red-600'
                                                : 'bg-navy-50 text-navy-400',
                                        )
                                    "
                                >
                                    <FileText class="size-[18px]" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block text-[13px] font-semibold text-navy-900"
                                        >{{ item.title }}</span
                                    >
                                    <span
                                        v-if="item.info"
                                        class="block truncate text-[11px] text-navy-500"
                                        >{{ item.info.name }}
                                        <template v-if="item.info.size">
                                            ·
                                            {{
                                                formatFileSize(item.info.size)
                                            }}</template
                                        ></span
                                    >
                                    <span
                                        v-else
                                        class="block text-[11px] text-navy-400"
                                        >{{ item.hint }}</span
                                    >
                                </span>
                            </div>
                            <div
                                v-if="issue.can.manage || item.info"
                                class="mt-2 flex flex-wrap justify-end gap-1"
                            >
                                <a
                                    v-if="item.info?.url"
                                    :href="item.info.url"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-navy-700 transition-colors hover:bg-navy-50"
                                >
                                    <Download class="size-3.5" /> Ochish
                                </a>
                                <template v-if="issue.can.manage">
                                    <button
                                        type="button"
                                        :disabled="uploading !== null"
                                        class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-brand-700 transition-colors hover:bg-brand-50 disabled:opacity-50"
                                        @click="pick(item.type)"
                                    >
                                        <LoaderCircle
                                            v-if="uploading === item.type"
                                            class="size-3.5 animate-spin"
                                        />
                                        <Upload v-else class="size-3.5" />
                                        {{
                                            item.info
                                                ? 'Almashtirish'
                                                : 'Yuklash'
                                        }}
                                    </button>
                                    <button
                                        v-if="item.info"
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-navy-500 transition-colors hover:bg-red-50 hover:text-red-600"
                                        @click="removeFile(item.type)"
                                    >
                                        <Trash2 class="size-3.5" />
                                    </button>
                                </template>
                                <input
                                    :ref="
                                        (el) =>
                                            (inputs[item.type] =
                                                el as HTMLInputElement)
                                    "
                                    type="file"
                                    accept="application/pdf,.pdf"
                                    class="hidden"
                                    @change="upload(item.type, $event)"
                                />
                            </div>
                            <p
                                v-if="fileErrors[item.type]"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ fileErrors[item.type] }}
                            </p>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>

    <IssueFormDialog
        v-model:open="editOpen"
        :url="issue.urls.update"
        method="put"
        :initial="{
            year: issue.year,
            volume: issue.volume,
            number: issue.number,
            doi: issue.doi,
            title: issue.title,
            description: issue.description,
        }"
    />

    <AddArticlesDialog
        v-model:open="addOpen"
        :url="issue.urls.attach"
        :articles="available"
    />

    <ActionDialog
        v-model:open="paginateOpen"
        title="Sahifalarni avtomatik hisoblash"
        description="Maqolalar joriy tartibda, har birining hajmi (betlar soni) bo'yicha ketma-ket raqamlanadi. Hajm «Nashr jarayoni» sahifasida kiritiladi."
        :icon="Calculator"
        confirm-text="Hisoblash"
        :processing="paginateForm.processing"
        @confirm="paginate"
    >
        <label class="grid gap-1.5">
            <span class="text-xs font-semibold text-navy-700"
                >Birinchi maqola sahifasi</span
            >
            <input
                v-model.number="paginateForm.start_page"
                type="number"
                min="1"
                :class="cn(inputClass, 'w-32 tabular-nums')"
            />
            <span
                v-if="paginateForm.errors.start_page"
                class="text-xs text-red-600"
                >{{ paginateForm.errors.start_page }}</span
            >
        </label>
    </ActionDialog>

    <ActionDialog
        v-model:open="publishOpen"
        title="Sonni chop etish"
        :description="`${issue.label} soni va undagi ${issue.summary.total} ta maqola saytda e'lon qilinadi, mualliflarga xabar yuboriladi. Chop etilgan maqolalarni keyin o'zgartirib bo'lmaydi.`"
        :icon="Send"
        confirm-text="Chop etish"
        :processing="publishing"
        @confirm="publish"
    />

    <ActionDialog
        v-model:open="deleteOpen"
        title="Sonni o'chirish"
        :description="`${issue.label} soni va uning fayllari o'chiriladi.`"
        :icon="Trash2"
        tone="danger"
        confirm-text="O'chirish"
        :processing="deleting"
        @confirm="destroy"
    />
</template>
