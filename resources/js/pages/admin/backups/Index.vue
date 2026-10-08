<script setup lang="ts">
import { Head, router, useForm, usePoll } from '@inertiajs/vue3';
import {
    Archive,
    CalendarClock,
    CircleCheck,
    CircleX,
    Clock,
    Database,
    DatabaseBackup,
    Download,
    FolderArchive,
    HardDrive,
    LoaderCircle,
    Play,
    Save,
    TerminalSquare,
    Trash2,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, ref, watch } from 'vue';
import MetricTile from '@/components/admin/people/MetricTile.vue';
import DeleteDialog from '@/components/admin/settings/DeleteDialog.vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import SectionCard from '@/components/admin/ui/SectionCard.vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import {
    formatDateTime,
    formatFileSize,
    formatNumber,
    timeAgo,
} from '@/lib/format';
import { inputClass, primaryButtonClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/backups';
import type { BackupItem, BackupSettings, BackupsPageProps } from '@/types';
import { t, tk } from '@/lib/i18n';

/**
 * Admin → Zaxira nusxa: qo'lda yaratish (to'liq / baza / fayllar), jadval, ro'yxat,
 * yuklab olish va o'chirish. Jarayon ketayotganda sahifa har 5 soniyada yangilanadi.
 */
const props = defineProps<BackupsPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: tk('Admin panel'), href: dashboard() },
            { title: tk('Zaxira nusxa'), href: index() },
        ],
    },
});

const inProgress = computed(() =>
    props.backups.some((b) => b.status === 'queued' || b.status === 'running'),
);

const { start, stop } = usePoll(
    5_000,
    { only: ['backups', 'stats'] },
    { autoStart: false },
);

watch(inProgress, (value) => (value ? start() : stop()), { immediate: true });

/* ---------- Yaratish ---------- */

const typeIcons: Record<BackupSettings['type'], Component> = {
    full: Archive,
    database: Database,
    files: FolderArchive,
};

const typeHints: Record<BackupSettings['type'], string> = {
    full: t("Baza va barcha yuklangan fayllar — to'liq tiklash uchun"),
    database: t('Faqat baza — tez va kichik hajm'),
    files: t('Maqola fayllari, PDF, muqova va rasmlar'),
};

const creating = ref<BackupSettings['type'] | null>(null);
const confirmType = ref<BackupSettings['type'] | null>(null);
const confirmOpen = ref(false);
const createError = ref<string | null>(null);

function askCreate(type: BackupSettings['type']): void {
    confirmType.value = type;
    confirmOpen.value = true;
    createError.value = null;
}

function create(): void {
    if (!confirmType.value) {
        return;
    }

    creating.value = confirmType.value;
    router.post(
        props.urls.store,
        { type: confirmType.value },
        {
            preserveScroll: true,
            onSuccess: () => (confirmOpen.value = false),
            onError: (e) =>
                (createError.value = Object.values(e)[0] ?? t('Xato')),
            onFinish: () => (creating.value = null),
        },
    );
}

/* ---------- Jadval ---------- */

const settingsForm = useForm<BackupSettings>({ ...props.settings });

function saveSettings(): void {
    settingsForm.put(props.urls.settings, {
        preserveScroll: true,
        onSuccess: () => settingsForm.defaults(),
    });
}

/* ---------- O'chirish ---------- */

const removing = ref<BackupItem | null>(null);
const removeOpen = ref(false);

function askRemove(item: BackupItem): void {
    removing.value = item;
    removeOpen.value = true;
}

/* ---------- Ko'rinish ---------- */

const tiles = computed(() => [
    {
        key: 'last',
        label: t('Oxirgi zaxira'),
        value: props.stats.last?.createdAt
            ? timeAgo(props.stats.last.createdAt)
            : t("Hali yo'q"),
        hint: props.stats.last?.type,
        icon: DatabaseBackup,
        tint: props.stats.last
            ? 'bg-emerald-50 text-emerald-600'
            : 'bg-amber-50 text-amber-600',
    },
    {
        key: 'count',
        label: t('Saqlangan arxivlar'),
        value: formatNumber(props.stats.count),
        hint: `Jami ${formatFileSize(props.stats.totalSize)}`,
        icon: Archive,
        tint: 'bg-brand-50 text-brand-600',
    },
    {
        key: 'next',
        label: t('Keyingi avtomatik'),
        value: props.stats.nextRun
            ? formatDateTime(props.stats.nextRun)
            : t("O'chirilgan"),
        hint: props.settings.enabled
            ? `Har kuni ${props.settings.time}`
            : t('Jadval yoqilmagan'),
        icon: CalendarClock,
        tint: 'bg-violet-50 text-violet-600',
    },
    {
        key: 'disk',
        label: t("Diskda bo'sh joy"),
        value:
            props.stats.freeSpace === null
                ? '—'
                : formatFileSize(props.stats.freeSpace),
        hint: 'storage/app/private/backups',
        icon: HardDrive,
        tint:
            props.stats.freeSpace !== null &&
            props.stats.freeSpace < 2 * 1024 * 1024 * 1024
                ? 'bg-red-50 text-red-600'
                : 'bg-sky-50 text-sky-600',
    },
]);

const statusMeta: Record<
    BackupItem['status'],
    { label: string; class: string; icon: Component }
> = {
    queued: {
        label: t('Navbatda'),
        class: 'bg-slate-100 text-slate-600',
        icon: Clock,
    },
    running: {
        label: t('Yaratilmoqda'),
        class: 'bg-amber-50 text-amber-700',
        icon: LoaderCircle,
    },
    done: {
        label: t('Tayyor'),
        class: 'bg-emerald-50 text-emerald-700',
        icon: CircleCheck,
    },
    failed: {
        label: t('Xato'),
        class: 'bg-red-50 text-red-700',
        icon: CircleX,
    },
};

function duration(ms: number | null): string {
    if (ms === null) {
        return '—';
    }

    return ms < 60_000
        ? `${(ms / 1000).toFixed(1)} s`
        : `${Math.round(ms / 60_000)} daq`;
}

/** "1 843 fayl · 3 daq" — hajm ostidagi izoh */
function meta(item: BackupItem): string {
    return [
        item.filesCount ? `${formatNumber(item.filesCount)} fayl` : null,
        item.durationMs !== null ? duration(item.durationMs) : null,
    ]
        .filter(Boolean)
        .join(' · ');
}

const restoreCommands = computed(() =>
    props.database === 'sqlite'
        ? 'unzip backup-….zip -d tiklash\ngunzip tiklash/database.sql.gz\nsqlite3 database/database.sqlite < tiklash/database.sql'
        : 'unzip backup-….zip -d tiklash\ngunzip tiklash/database.sql.gz\nmysql -u USER -p DB_NAME < tiklash/database.sql\ncp -r tiklash/files/public/* storage/app/public/\ncp -r tiklash/files/private/* storage/app/private/',
);
</script>

<template>
    <Head :title="t('Zaxira nusxa')" />

    <div
        class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 text-navy-900 md:p-6"
    >
        <PageHeader
            :title="t('Zaxira nusxa')"
            :description="
                t(
                    'Ma\'lumotlar bazasi va yuklangan fayllarning arxivlari: qo\'lda yoki har kuni avtomatik',
                )
            "
        >
            <template #before>
                <span
                    class="mb-2 inline-flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"
                >
                    <DatabaseBackup class="size-5" />
                </span>
            </template>
        </PageHeader>

        <section
            class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4"
            :aria-label="t('Holat')"
        >
            <MetricTile
                v-for="tile in tiles"
                :key="tile.key"
                :label="tile.label"
                :value="tile.value"
                :hint="tile.hint"
                :icon="tile.icon"
                :tint="tile.tint"
            />
        </section>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_24rem]">
            <div class="flex min-w-0 flex-col gap-5">
                <!-- Yaratish -->
                <section class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <button
                        v-for="t in types"
                        :key="t.value"
                        type="button"
                        :disabled="inProgress || creating !== null"
                        class="group flex items-start gap-3 rounded-xl border border-line bg-white p-4 text-left shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-[0_16px_34px_-20px_rgba(0,36,66,0.45)] disabled:pointer-events-none disabled:opacity-55"
                        @click="askCreate(t.value)"
                    >
                        <span
                            class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 transition-all duration-300 group-hover:scale-110 group-hover:bg-brand-600 group-hover:text-white"
                        >
                            <component
                                :is="typeIcons[t.value]"
                                class="size-5"
                            />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span
                                class="flex items-center justify-between gap-2 text-[14px] font-bold text-navy-950"
                            >
                                {{ t.label }}
                                <Play
                                    class="size-4 shrink-0 text-navy-300 transition-all group-hover:translate-x-0.5 group-hover:text-brand-600"
                                />
                            </span>
                            <span class="mt-0.5 block text-xs text-navy-500">{{
                                typeHints[t.value]
                            }}</span>
                        </span>
                    </button>
                </section>

                <!-- Ro'yxat -->
                <SectionCard
                    :title="t('Arxivlar')"
                    :description="
                        inProgress
                            ? t(
                                  'Zaxira yaratilmoqda — sahifa avtomatik yangilanadi',
                              )
                            : t('So\'nggi :count ta yozuv', {
                                  count: backups.length,
                              })
                    "
                    :icon="Archive"
                >
                    <div
                        v-if="backups.length"
                        class="-mx-5 -my-5 overflow-x-auto"
                    >
                        <table
                            class="w-full min-w-[600px] text-left text-[13px]"
                        >
                            <thead>
                                <tr
                                    class="border-b border-line bg-[#f8fafc] text-[11px] font-semibold tracking-wide text-navy-500 uppercase"
                                >
                                    <th class="py-2.5 pl-5">
                                        {{ t('Arxiv') }}
                                    </th>
                                    <th class="py-2.5 pr-4">
                                        {{ t('Holat') }}
                                    </th>
                                    <th class="py-2.5 pr-4 text-right">
                                        {{ t('Hajmi') }}
                                    </th>
                                    <th class="py-2.5 pr-4">
                                        {{ t('Yaratilgan') }}
                                    </th>
                                    <th class="w-20 py-2.5 pr-5" />
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                <tr
                                    v-for="item in backups"
                                    :key="item.uuid"
                                    class="group transition-colors hover:bg-brand-50/40"
                                >
                                    <td class="py-3 pl-5">
                                        <div class="flex items-center gap-3">
                                            <span
                                                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-[#eef3fa] text-navy-500 transition-colors group-hover:bg-brand-50 group-hover:text-brand-600"
                                            >
                                                <component
                                                    :is="typeIcons[item.type]"
                                                    class="size-4"
                                                />
                                            </span>
                                            <div class="min-w-0">
                                                <p
                                                    class="font-semibold text-navy-900"
                                                >
                                                    {{ item.typeLabel }}
                                                </p>
                                                <p
                                                    class="truncate font-mono text-[11px] text-navy-400"
                                                >
                                                    {{
                                                        item.fileName ??
                                                        (item.trigger ===
                                                        'schedule'
                                                            ? "jadval bo'yicha"
                                                            : "qo'lda")
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                        <p
                                            v-if="item.error"
                                            class="mt-1.5 max-w-xl text-xs [overflow-wrap:anywhere] text-red-600"
                                        >
                                            {{ item.error }}
                                        </p>
                                    </td>
                                    <td class="py-3 pr-4">
                                        <span
                                            :class="
                                                cn(
                                                    'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap',
                                                    statusMeta[item.status]
                                                        .class,
                                                )
                                            "
                                        >
                                            <component
                                                :is="
                                                    statusMeta[item.status].icon
                                                "
                                                :class="
                                                    cn(
                                                        'size-3',
                                                        item.status ===
                                                            'running' &&
                                                            'animate-spin',
                                                    )
                                                "
                                            />
                                            {{ statusMeta[item.status].label }}
                                        </span>
                                    </td>
                                    <td
                                        class="py-3 pr-4 text-right whitespace-nowrap text-navy-700 tabular-nums"
                                    >
                                        {{
                                            item.size
                                                ? formatFileSize(item.size)
                                                : '—'
                                        }}
                                        <span
                                            class="block text-[11px] text-navy-400"
                                            >{{ meta(item) }}</span
                                        >
                                    </td>
                                    <td
                                        class="py-3 pr-4 whitespace-nowrap text-navy-600"
                                    >
                                        <span class="tabular-nums">{{
                                            formatDateTime(item.createdAt)
                                        }}</span>
                                        <span
                                            class="block text-[11px] text-navy-400"
                                            >{{
                                                item.creator ??
                                                (item.trigger === 'schedule'
                                                    ? t('Avtomatik')
                                                    : t('Konsol'))
                                            }}</span
                                        >
                                    </td>
                                    <td class="py-3 pr-5">
                                        <div class="flex justify-end gap-1">
                                            <a
                                                v-if="item.downloadUrl"
                                                :href="item.downloadUrl"
                                                class="flex size-8 items-center justify-center rounded-lg border border-line text-navy-500 transition-all hover:-translate-y-px hover:border-brand-200 hover:text-brand-600"
                                                :title="t('Yuklab olish')"
                                            >
                                                <Download class="size-4" />
                                            </a>
                                            <button
                                                v-if="
                                                    item.status === 'done' ||
                                                    item.status === 'failed'
                                                "
                                                type="button"
                                                class="flex size-8 items-center justify-center rounded-lg border border-line text-navy-500 transition-all hover:-translate-y-px hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                                                :title="t('O\'chirish')"
                                                @click="askRemove(item)"
                                            >
                                                <Trash2 class="size-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div
                        v-else
                        class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-line px-6 py-10 text-center"
                    >
                        <DatabaseBackup class="size-8 text-navy-300" />
                        <p class="text-sm font-semibold text-navy-700">
                            {{ t("Hali zaxira nusxa yo'q") }}
                        </p>
                        <p class="text-xs text-navy-500">
                            {{
                                t(
                                    "Yuqoridan «To'liq» zaxirani yarating yoki avtomatik jadvalni yoqing.",
                                )
                            }}
                        </p>
                    </div>
                </SectionCard>
            </div>

            <aside class="flex min-w-0 flex-col gap-5">
                <SectionCard
                    :title="t('Avtomatik zaxira')"
                    :description="
                        t(
                            'Har kuni belgilangan vaqtda (server cron: schedule:run)',
                        )
                    "
                    :icon="CalendarClock"
                >
                    <form class="grid gap-4" @submit.prevent="saveSettings">
                        <label
                            :class="
                                cn(
                                    'flex cursor-pointer items-center justify-between gap-3 rounded-xl border px-3.5 py-3 transition-all',
                                    settingsForm.enabled
                                        ? 'border-brand-300 bg-brand-50/60'
                                        : 'border-line hover:border-brand-200',
                                )
                            "
                        >
                            <span>
                                <span
                                    class="block text-[13px] font-semibold text-navy-900"
                                    >{{ t('Har kuni avtomatik') }}</span
                                >
                                <span class="block text-xs text-navy-500">{{
                                    settingsForm.enabled
                                        ? t('Yoqilgan')
                                        : "O'chirilgan"
                                }}</span>
                            </span>
                            <input
                                v-model="settingsForm.enabled"
                                type="checkbox"
                                class="size-5 accent-brand-600"
                            />
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <FormField
                                :label="t('Vaqti')"
                                for="bk-time"
                                :error="settingsForm.errors.time"
                            >
                                <input
                                    id="bk-time"
                                    v-model="settingsForm.time"
                                    type="time"
                                    :class="cn(inputClass, 'tabular-nums')"
                                />
                            </FormField>
                            <FormField
                                :label="t('Saqlanadi')"
                                for="bk-keep"
                                :error="settingsForm.errors.keep"
                                :hint="t('oxirgi N ta')"
                            >
                                <input
                                    id="bk-keep"
                                    v-model.number="settingsForm.keep"
                                    type="number"
                                    min="1"
                                    max="365"
                                    :class="cn(inputClass, 'tabular-nums')"
                                />
                            </FormField>
                        </div>
                        <FormField
                            :label="t('Turi')"
                            for="bk-type"
                            :error="settingsForm.errors.type"
                        >
                            <SelectInput
                                id="bk-type"
                                v-model="settingsForm.type"
                            >
                                <option
                                    v-for="t in types"
                                    :key="t.value"
                                    :value="t.value"
                                >
                                    {{ t.label }}
                                </option>
                            </SelectInput>
                        </FormField>
                        <button
                            type="submit"
                            :disabled="
                                settingsForm.processing || !settingsForm.isDirty
                            "
                            :class="cn(primaryButtonClass, 'w-full')"
                        >
                            <LoaderCircle
                                v-if="settingsForm.processing"
                                class="size-4 animate-spin"
                            />
                            <Save v-else class="size-4" />
                            {{ t('Saqlash') }}
                        </button>
                    </form>
                </SectionCard>

                <SectionCard
                    :title="t('Tiklash')"
                    :description="
                        t('Xavfsizlik uchun faqat serverda, qo\'lda bajariladi')
                    "
                    :icon="TerminalSquare"
                >
                    <ol
                        class="mb-3 list-inside list-decimal space-y-1 text-xs text-navy-600"
                    >
                        <li>
                            {{
                                t(
                                    "Saytni texnik rejimga o'tkazing: php artisan down",
                                )
                            }}
                        </li>
                        <li>
                            {{
                                t(
                                    'Arxivni serverga yuklab, quyidagilarni bajaring',
                                )
                            }}
                        </li>
                        <li>{{ t("So'ng: php artisan up") }}</li>
                    </ol>
                    <pre
                        class="overflow-x-auto rounded-lg bg-navy-950 px-3 py-2.5 font-mono text-[11px] leading-relaxed text-emerald-200"
                        >{{ restoreCommands }}</pre>
                    <p class="mt-2 text-[11px] text-navy-400">
                        {{
                            t(
                                "Arxivda maxfiy ma'lumotlar bor — uni xavfsiz joyda saqlang va boshqalarga bermang.",
                            )
                        }}
                    </p>
                </SectionCard>
            </aside>
        </div>

        <ActionDialog
            v-model:open="confirmOpen"
            :title="t('Zaxira nusxa yaratish')"
            :description="
                t(
                    '«:label» arxivi navbatda yaratiladi. Hajmga qarab bir necha daqiqa davom etishi mumkin.',
                    {
                        label:
                            types.find((t) => t.value === confirmType)?.label ??
                            '',
                    },
                )
            "
            :icon="DatabaseBackup"
            :confirm-text="t('Boshlash')"
            :processing="creating !== null"
            @confirm="create"
        >
            <p
                v-if="createError"
                class="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700"
            >
                {{ createError }}
            </p>
        </ActionDialog>

        <DeleteDialog
            v-model:open="removeOpen"
            :url="removing?.destroyUrl ?? null"
            :title="t('Arxivni o\'chirish')"
            :description="
                t(':fileName butunlay o\'chiriladi.', {
                    fileName: removing?.fileName ?? t('Arxiv'),
                })
            "
        />
    </div>
</template>
