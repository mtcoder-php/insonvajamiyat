<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    BadgeCheck,
    Check,
    ExternalLink,
    FileText,
    GripVertical,
    Hourglass,
    LoaderCircle,
    PenLine,
    Trash2,
    X,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import { inputClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import type { IssueArticleRow, IssueDetail } from '@/types';

/**
 * Son tarkibi: tartib (sudrab tashlash yoki ↑↓), rukn, sahifalar, nashrga tayyorlik.
 */
const props = defineProps<{ issue: IssueDetail }>();

const rows = ref<IssueArticleRow[]>([...props.issue.articles]);
watch(
    () => props.issue.articles,
    (value) => (rows.value = [...value]),
);

const saving = ref(false);

function saveOrder(): void {
    saving.value = true;
    router.put(
        props.issue.urls.reorder,
        { order: rows.value.map((r) => r.id) },
        {
            preserveScroll: true,
            onFinish: () => (saving.value = false),
            onError: () => (rows.value = [...props.issue.articles]),
        },
    );
}

function move(index: number, delta: number): void {
    const target = index + delta;

    if (target < 0 || target >= rows.value.length) {
        return;
    }

    const next = [...rows.value];
    const [item] = next.splice(index, 1);
    next.splice(target, 0, item);
    rows.value = next;
    saveOrder();
}

// Sudrab tashlash (native HTML5 drag & drop)
const dragIndex = ref<number | null>(null);
const overIndex = ref<number | null>(null);

function onDrop(index: number): void {
    if (dragIndex.value !== null && dragIndex.value !== index) {
        move(dragIndex.value, index - dragIndex.value);
    }

    dragIndex.value = null;
    overIndex.value = null;
}

// Qatorni tahrirlash: rukn va sahifalar
const editing = ref<number | null>(null);
const form = useForm<{
    section: string;
    page_from: number | null;
    page_to: number | null;
}>({ section: '', page_from: null, page_to: null });

function edit(row: IssueArticleRow): void {
    editing.value = row.id;
    form.defaults({
        section: row.section ?? '',
        page_from: row.pageFrom,
        page_to: row.pageTo,
    });
    form.reset();
    form.clearErrors();
}

function saveRow(row: IssueArticleRow): void {
    form.put(row.urls.update, {
        preserveScroll: true,
        onSuccess: () => (editing.value = null),
    });
}

// Sondan chiqarish
const removing = ref<IssueArticleRow | null>(null);
const removeOpen = ref(false);
const removeBusy = ref(false);

function askRemove(row: IssueArticleRow): void {
    removing.value = row;
    removeOpen.value = true;
}

function remove(): void {
    if (!removing.value) {
        return;
    }

    removeBusy.value = true;
    router.delete(removing.value.urls.destroy, {
        preserveScroll: true,
        onSuccess: () => (removeOpen.value = false),
        onFinish: () => (removeBusy.value = false),
    });
}

const pages = (row: IssueArticleRow): string =>
    row.pageFrom && row.pageTo ? `${row.pageFrom}–${row.pageTo}` : '—';
</script>

<template>
    <div>
        <div
            v-if="saving"
            class="mb-2 flex items-center gap-2 text-xs text-navy-500"
        >
            <LoaderCircle class="size-3.5 animate-spin" /> Tartib saqlanmoqda...
        </div>

        <ol v-if="rows.length" class="grid gap-2">
            <li
                v-for="(row, index) in rows"
                :key="row.id"
                :draggable="issue.can.manage && editing === null"
                :class="
                    cn(
                        'group rounded-xl border bg-white transition-all',
                        overIndex === index && dragIndex !== index
                            ? 'border-brand-400 ring-4 ring-brand-100'
                            : 'border-line hover:border-brand-200 hover:shadow-[0_12px_26px_-20px_rgba(0,36,66,0.5)]',
                        dragIndex === index && 'opacity-50',
                    )
                "
                @dragstart="dragIndex = index"
                @dragover.prevent="overIndex = index"
                @dragleave="overIndex = null"
                @drop.prevent="onDrop(index)"
                @dragend="
                    dragIndex = null;
                    overIndex = null;
                "
            >
                <div
                    class="flex flex-wrap items-start gap-3 p-3 sm:flex-nowrap"
                >
                    <span
                        class="flex shrink-0 flex-col items-center gap-0.5 pt-0.5"
                    >
                        <GripVertical
                            v-if="issue.can.manage"
                            class="size-4 cursor-grab text-navy-300 group-hover:text-navy-500"
                            aria-hidden="true"
                        />
                        <span
                            class="flex size-7 items-center justify-center rounded-lg bg-navy-950 text-xs font-bold text-white tabular-nums"
                            >{{ index + 1 }}</span
                        >
                    </span>

                    <div class="min-w-0 flex-1 basis-48">
                        <p
                            v-if="row.section && editing !== row.id"
                            class="mb-0.5 text-[10px] font-bold tracking-wide text-brand-700 uppercase"
                        >
                            {{ row.section }}
                        </p>
                        <p
                            class="line-clamp-2 text-[13px] font-semibold text-navy-900"
                        >
                            {{ row.title }}
                        </p>
                        <p class="mt-0.5 truncate text-xs text-navy-500">
                            {{ row.authors || '—' }}
                            <template v-if="row.subject">
                                · {{ row.subject }}</template
                            >
                        </p>
                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            <span
                                class="rounded-md bg-navy-50 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-navy-500"
                                >#{{ row.code }}</span
                            >
                            <span
                                v-if="row.ready"
                                class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-200 ring-inset"
                            >
                                <BadgeCheck class="size-3" />
                                {{
                                    row.status === 'published'
                                        ? 'Nashr etilgan'
                                        : 'Nashrga tayyor'
                                }}
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-amber-200 ring-inset"
                            >
                                <Hourglass class="size-3" />
                                {{ row.statusLabel }}
                            </span>
                            <span
                                v-if="
                                    !row.hasFinalPdf &&
                                    row.status !== 'published'
                                "
                                class="inline-flex items-center gap-1 rounded-full bg-navy-50 px-2 py-0.5 text-[10px] font-semibold text-navy-500"
                            >
                                <FileText class="size-3" /> PDF yo'q
                            </span>
                        </div>

                        <!-- Tahrirlash -->
                        <form
                            v-if="editing === row.id"
                            class="mt-3 grid gap-2 rounded-lg bg-[#f5f8fc] p-3 sm:grid-cols-[minmax(0,1fr)_5rem_5rem_auto]"
                            @submit.prevent="saveRow(row)"
                        >
                            <input
                                v-model="form.section"
                                type="text"
                                maxlength="120"
                                placeholder="Rukn (masalan: Tarix)"
                                aria-label="Rukn"
                                :class="cn(inputClass, 'h-9 text-[13px]')"
                            />
                            <input
                                v-model.number="form.page_from"
                                type="number"
                                min="1"
                                placeholder="dan"
                                aria-label="Boshlang'ich sahifa"
                                :class="
                                    cn(
                                        inputClass,
                                        'h-9 text-[13px] tabular-nums',
                                    )
                                "
                            />
                            <input
                                v-model.number="form.page_to"
                                type="number"
                                min="1"
                                placeholder="gacha"
                                aria-label="Oxirgi sahifa"
                                :class="
                                    cn(
                                        inputClass,
                                        'h-9 text-[13px] tabular-nums',
                                    )
                                "
                            />
                            <span class="flex gap-1">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="flex size-9 items-center justify-center rounded-lg bg-brand-600 text-white transition-colors hover:bg-brand-500 disabled:opacity-60"
                                    aria-label="Saqlash"
                                >
                                    <Check class="size-4" />
                                </button>
                                <button
                                    type="button"
                                    class="flex size-9 items-center justify-center rounded-lg border border-line text-navy-600 transition-colors hover:bg-white"
                                    aria-label="Bekor qilish"
                                    @click="editing = null"
                                >
                                    <X class="size-4" />
                                </button>
                            </span>
                            <p
                                v-if="Object.keys(form.errors).length"
                                class="text-xs text-red-600 sm:col-span-4"
                            >
                                {{ Object.values(form.errors)[0] }}
                            </p>
                        </form>
                    </div>

                    <div
                        class="flex w-full shrink-0 items-center justify-between gap-2 border-t border-line pt-2 sm:w-auto sm:flex-col sm:items-end sm:border-0 sm:pt-0"
                    >
                        <span
                            class="rounded-lg bg-[#f5f8fc] px-2 py-1 text-xs font-semibold text-navy-800 tabular-nums"
                            :title="
                                row.pagesCount
                                    ? `${row.pagesCount} bet`
                                    : 'Hajmi noma\'lum'
                            "
                        >
                            {{ pages(row) }}
                        </span>
                        <span class="flex items-center gap-0.5">
                            <template v-if="issue.can.manage">
                                <button
                                    type="button"
                                    :disabled="index === 0 || saving"
                                    class="rounded-md p-1.5 text-navy-400 transition-colors hover:bg-navy-50 hover:text-navy-800 disabled:opacity-30"
                                    aria-label="Yuqoriga"
                                    @click="move(index, -1)"
                                >
                                    <ArrowUp class="size-3.5" />
                                </button>
                                <button
                                    type="button"
                                    :disabled="
                                        index === rows.length - 1 || saving
                                    "
                                    class="rounded-md p-1.5 text-navy-400 transition-colors hover:bg-navy-50 hover:text-navy-800 disabled:opacity-30"
                                    aria-label="Pastga"
                                    @click="move(index, 1)"
                                >
                                    <ArrowDown class="size-3.5" />
                                </button>
                            </template>
                            <button
                                v-if="row.editable"
                                type="button"
                                class="rounded-md p-1.5 text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700"
                                aria-label="Rukn va sahifalar"
                                @click="edit(row)"
                            >
                                <PenLine class="size-3.5" />
                            </button>
                            <Link
                                :href="row.productionUrl"
                                class="rounded-md p-1.5 text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700"
                                aria-label="Nashr jarayonida ochish"
                            >
                                <ExternalLink class="size-3.5" />
                            </Link>
                            <button
                                v-if="row.editable"
                                type="button"
                                class="rounded-md p-1.5 text-navy-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                aria-label="Sondan chiqarish"
                                @click="askRemove(row)"
                            >
                                <Trash2 class="size-3.5" />
                            </button>
                        </span>
                    </div>
                </div>
            </li>
        </ol>

        <div
            v-else
            class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-navy-200 py-12 text-center"
        >
            <FileText class="size-8 text-navy-300" />
            <p class="text-sm text-navy-500">Songa hali maqola qo'shilmagan</p>
        </div>

        <ActionDialog
            v-model:open="removeOpen"
            title="Maqolani sondan chiqarish"
            :description="
                removing
                    ? `«${removing.title}» sondan chiqariladi, sahifalari tozalanadi va bosh muharrir tasdig'i bekor bo'ladi.`
                    : undefined
            "
            :icon="Trash2"
            tone="danger"
            confirm-text="Chiqarish"
            :processing="removeBusy"
            @confirm="remove"
        />
    </div>
</template>
