<script setup lang="ts">
import {
    CalendarRange,
    Check,
    ChevronDown,
    Download,
    FileSpreadsheet,
    Printer,
} from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { inputClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import type { ReportExportLink, ReportFilters, ReportOption } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Davr (tayyor variantlar yoki ixtiyoriy sana oralig'i), fan yo'nalishi va eksport menyusi.
 */
const props = defineProps<{
    filters: ReportFilters;
    subjects: ReportOption[];
    exports: ReportExportLink[];
    printUrl: string;
}>();

const emit = defineEmits<{
    apply: [query: { from: string; to: string; subject: string | null }];
}>();

function iso(date: Date): string {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');

    return `${y}-${m}-${d}`;
}

function fmt(value: string): string {
    const [y, m, d] = value.split('-');

    return `${d}.${m}.${y}`;
}

type Preset = { key: string; label: string; range: () => [Date, Date] };

const presets: Preset[] = [
    {
        key: '7d',
        label: t('Oxirgi 7 kun'),
        range: () => {
            const to = new Date();
            const from = new Date(to);
            from.setDate(to.getDate() - 6);

            return [from, to];
        },
    },
    {
        key: '30d',
        label: t('Oxirgi 30 kun'),
        range: () => {
            const to = new Date();
            const from = new Date(to);
            from.setDate(to.getDate() - 29);

            return [from, to];
        },
    },
    {
        key: 'month',
        label: t('Joriy oy'),
        range: () => {
            const to = new Date();

            return [new Date(to.getFullYear(), to.getMonth(), 1), to];
        },
    },
    {
        key: '3m',
        label: t('Oxirgi 3 oy'),
        range: () => {
            const to = new Date();

            return [new Date(to.getFullYear(), to.getMonth() - 2, 1), to];
        },
    },
    {
        key: '6m',
        label: t('Oxirgi 6 oy'),
        range: () => {
            const to = new Date();

            return [new Date(to.getFullYear(), to.getMonth() - 5, 1), to];
        },
    },
    {
        key: '12m',
        label: t('Oxirgi 12 oy'),
        range: () => {
            const to = new Date();

            return [new Date(to.getFullYear(), to.getMonth() - 11, 1), to];
        },
    },
    {
        key: 'year',
        label: t('Joriy yil'),
        range: () => {
            const to = new Date();

            return [new Date(to.getFullYear(), 0, 1), to];
        },
    },
    {
        key: 'prev-year',
        label: t("O'tgan yil"),
        range: () => {
            const year = new Date().getFullYear() - 1;

            return [new Date(year, 0, 1), new Date(year, 11, 31)];
        },
    },
];

const activePreset = computed(
    () =>
        presets.find((p) => {
            const [from, to] = p.range();

            return (
                iso(from) === props.filters.from && iso(to) === props.filters.to
            );
        })?.key ?? null,
);

const custom = reactive({ from: props.filters.from, to: props.filters.to });
const subject = ref<string | null>(props.filters.subject);

watch(
    () => props.filters,
    (f) => {
        custom.from = f.from;
        custom.to = f.to;
        subject.value = f.subject;
    },
);

function applyPreset(preset: Preset): void {
    const [from, to] = preset.range();
    emit('apply', { from: iso(from), to: iso(to), subject: subject.value });
}

function applyCustom(): void {
    if (!custom.from || !custom.to) {
        return;
    }

    const [from, to] =
        custom.from <= custom.to
            ? [custom.from, custom.to]
            : [custom.to, custom.from];
    emit('apply', { from, to, subject: subject.value });
}

watch(subject, (value) => {
    if (value !== props.filters.subject) {
        emit('apply', {
            from: props.filters.from,
            to: props.filters.to,
            subject: value,
        });
    }
});

const today = iso(new Date());
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <DropdownMenu>
            <DropdownMenuTrigger
                class="group inline-flex h-10 items-center gap-2 rounded-lg border border-line bg-white px-3 text-[13px] font-medium text-navy-800 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all outline-none hover:border-brand-300 hover:shadow-md focus-visible:ring-2 focus-visible:ring-brand-200 data-[state=open]:border-brand-400"
            >
                <CalendarRange class="size-4 text-brand-600" />
                <span class="tabular-nums"
                    >{{ fmt(filters.from) }} – {{ fmt(filters.to) }}</span
                >
                <ChevronDown
                    class="size-4 text-navy-400 transition-transform group-data-[state=open]:rotate-180"
                />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-72 p-2">
                <DropdownMenuLabel
                    class="text-[11px] text-navy-400 uppercase"
                    >{{ t('Tez tanlash') }}</DropdownMenuLabel
                >
                <div class="grid grid-cols-2 gap-1">
                    <DropdownMenuItem
                        v-for="preset in presets"
                        :key="preset.key"
                        :class="
                            cn(
                                'cursor-pointer justify-between text-[13px]',
                                activePreset === preset.key &&
                                    'bg-brand-50 font-semibold text-brand-700',
                            )
                        "
                        @select="applyPreset(preset)"
                    >
                        {{ preset.label }}
                        <Check
                            v-if="activePreset === preset.key"
                            class="size-3.5"
                        />
                    </DropdownMenuItem>
                </div>
                <DropdownMenuSeparator />
                <DropdownMenuLabel
                    class="text-[11px] text-navy-400 uppercase"
                    >{{ t('Ixtiyoriy oraliq') }}</DropdownMenuLabel
                >
                <form
                    class="grid gap-2 px-2 pb-1"
                    @submit.prevent="applyCustom"
                    @keydown.stop
                >
                    <div class="grid grid-cols-2 gap-2">
                        <label class="text-[11px] text-navy-500">
                            {{ t('Boshlanish') }}
                            <input
                                v-model="custom.from"
                                type="date"
                                :max="today"
                                :class="
                                    cn(inputClass, 'mt-1 h-9 px-2 text-[13px]')
                                "
                            />
                        </label>
                        <label class="text-[11px] text-navy-500">
                            {{ t('Tugash') }}
                            <input
                                v-model="custom.to"
                                type="date"
                                :max="today"
                                :class="
                                    cn(inputClass, 'mt-1 h-9 px-2 text-[13px]')
                                "
                            />
                        </label>
                    </div>
                    <button
                        type="submit"
                        class="h-9 rounded-lg bg-navy-900 text-[13px] font-semibold text-white transition-colors hover:bg-brand-700"
                    >
                        {{ t("Qo'llash") }}
                    </button>
                </form>
            </DropdownMenuContent>
        </DropdownMenu>

        <SelectInput
            v-model="subject"
            :aria-label="t('Fan yo\'nalishi')"
            class="w-56 text-[13px]"
        >
            <option :value="null">{{ t("Barcha yo'nalishlar") }}</option>
            <option v-for="s in subjects" :key="s.value" :value="s.value">
                {{ s.label }}
            </option>
        </SelectInput>

        <DropdownMenu>
            <DropdownMenuTrigger
                class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-600 px-4 text-[13px] font-semibold text-white shadow-[0_10px_22px_-12px_rgba(0,108,246,0.9)] transition-all outline-none hover:-translate-y-px hover:bg-brand-700 focus-visible:ring-2 focus-visible:ring-brand-300"
            >
                <Download class="size-4" /> {{ t('Eksport') }}
                <ChevronDown class="size-4 opacity-80" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-60">
                <DropdownMenuLabel
                    class="text-[11px] text-navy-400 uppercase"
                    >{{ t('Excel (CSV)') }}</DropdownMenuLabel
                >
                <DropdownMenuItem
                    v-for="item in exports"
                    :key="item.type"
                    as-child
                >
                    <a :href="item.url" class="cursor-pointer">
                        <FileSpreadsheet class="size-4 text-emerald-600" />
                        {{ item.label }}
                    </a>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem as-child>
                    <a
                        :href="printUrl"
                        target="_blank"
                        rel="noopener"
                        class="cursor-pointer"
                    >
                        <Printer class="size-4 text-red-600" />
                        {{ t('Umumiy hisobot (PDF)') }}
                    </a>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
