<script setup lang="ts">
import { router, usePoll } from '@inertiajs/vue3';
import {
    BookCopy,
    CircleAlert,
    CircleCheck,
    CircleDashed,
    Download,
    LoaderCircle,
    RefreshCw,
    Sparkles,
    TriangleAlert,
} from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { formatDateTime, formatFileSize } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { IssueFileInfo, IssuePdfBuild } from '@/types';
import { t, tc } from '@/lib/i18n';

/**
 * "To'liq son PDF": muqova + mundarija + maqolalarning yakuniy PDF lari bitta faylga
 * avtomatik yig'iladi (xatcho'plar va jurnal sahifa raqamlari bilan). Navbatda bajariladi —
 * jarayon davomida holat har 3 soniyada yangilanadi.
 */
const props = defineProps<{
    build: IssuePdfBuild;
    file: IssueFileInfo | null;
    canManage: boolean;
}>();

const starting = ref(false);
const error = ref<string | null>(null);

const { start, stop } = usePoll(
    3000,
    { only: ['issue'] },
    { autoStart: false },
);

watch(
    () => props.build.busy,
    (busy) => (busy ? start() : stop()),
    { immediate: true },
);

onBeforeUnmount(stop);

const required = computed(() => props.build.checks.filter((c) => c.required));
const optional = computed(() => props.build.checks.filter((c) => !c.required));

function buildPdf(): void {
    starting.value = true;
    error.value = null;
    router.post(
        props.build.buildUrl,
        {},
        {
            preserveScroll: true,
            only: ['issue'],
            onError: (errors) =>
                (error.value = errors.pdf ?? t("Yig'ishni boshlab bo'lmadi.")),
            onFinish: () => (starting.value = false),
        },
    );
}
</script>

<template>
    <section
        class="relative isolate overflow-hidden rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-shadow duration-300 hover:shadow-[0_12px_32px_-18px_rgba(0,36,66,0.25)]"
    >
        <div
            class="absolute -top-12 -right-12 -z-10 size-36 rounded-full bg-gradient-to-br from-brand-100 to-red-50 opacity-80 blur-2xl"
            aria-hidden="true"
        />
        <h2
            class="flex items-center gap-2 font-sans text-[15px] font-bold text-navy-950"
        >
            <span
                class="flex size-8 items-center justify-center rounded-lg bg-gradient-to-br from-red-500 to-rose-600 text-white shadow-sm"
            >
                <BookCopy class="size-4" />
            </span>
            {{ t("To'liq son PDF") }}
        </h2>
        <p class="mt-2 text-xs leading-relaxed text-navy-600">
            {{
                t(
                    "Muqova, mundarija va maqolalarning yakuniy PDF lari son tartibida bitta faylga yig'iladi — xatcho'plar va jurnal sahifa raqamlari bilan.",
                )
            }}
        </p>

        <!-- Holat -->
        <div
            v-if="build.busy"
            class="mt-4 flex items-center gap-3 rounded-lg border border-brand-100 bg-brand-50/70 px-3 py-3"
            role="status"
        >
            <LoaderCircle class="size-5 shrink-0 animate-spin text-brand-600" />
            <div class="text-xs text-navy-700">
                <p class="font-semibold text-navy-900">
                    {{
                        build.status === 'queued'
                            ? t('Navbatda…')
                            : "Yig'ilmoqda…"
                    }}
                </p>
                <p>
                    {{
                        t(
                            "Bir necha soniya — tayyor bo'lgach shu yerda paydo bo'ladi.",
                        )
                    }}
                </p>
            </div>
        </div>
        <div
            v-else-if="build.status === 'failed'"
            class="mt-4 flex items-start gap-2 rounded-lg border border-red-100 bg-red-50/70 px-3 py-2.5 text-xs text-red-800"
        >
            <CircleAlert class="mt-0.5 size-4 shrink-0 text-red-500" />
            <span
                ><b>{{ t("Yig'ilmadi:") }}</b> {{ build.error }}</span
            >
        </div>
        <div
            v-else-if="file"
            :class="
                cn(
                    'mt-4 rounded-lg border px-3 py-2.5 text-xs',
                    build.stale
                        ? 'border-amber-200 bg-amber-50/70 text-amber-900'
                        : 'border-emerald-100 bg-emerald-50/60 text-emerald-900',
                )
            "
        >
            <p class="flex items-center gap-1.5 font-semibold">
                <TriangleAlert
                    v-if="build.stale"
                    class="size-4 text-amber-600"
                />
                <CircleCheck v-else class="size-4 text-emerald-600" />
                {{
                    build.auto
                        ? build.stale
                            ? "Eskirgan — maqolalar o'zgargan"
                            : "Avtomatik yig'ilgan"
                        : "Qo'lda yuklangan"
                }}
            </p>
            <p class="mt-0.5 text-navy-600">
                <template v-if="build.pages"
                    >{{ tc(':count bet', build.pages) }} ·
                </template>
                <template v-if="file.size"
                    >{{ formatFileSize(file.size) }} ·
                </template>
                <template v-if="build.builtAt">{{
                    formatDateTime(build.builtAt)
                }}</template>
            </p>
            <a
                v-if="file.url"
                :href="file.url"
                target="_blank"
                rel="noopener"
                class="mt-2 inline-flex items-center gap-1 font-semibold text-brand-700 hover:text-brand-600"
            >
                <Download class="size-3.5" /> {{ t('Ochish') }}
            </a>
        </div>

        <!-- Tekshiruv -->
        <ul class="mt-4 grid grid-cols-1 gap-1.5 text-[12.5px]">
            <li
                v-for="check in [...required, ...optional]"
                :key="check.key"
                class="flex items-start gap-2"
            >
                <CircleCheck
                    v-if="check.ok"
                    class="mt-0.5 size-4 shrink-0 text-emerald-600"
                />
                <CircleAlert
                    v-else-if="check.required"
                    class="mt-0.5 size-4 shrink-0 text-red-500"
                />
                <CircleDashed
                    v-else
                    class="mt-0.5 size-4 shrink-0 text-amber-500"
                />
                <span class="min-w-0">
                    <span
                        :class="
                            cn(
                                'font-medium',
                                check.ok
                                    ? 'text-navy-800'
                                    : check.required
                                      ? 'text-red-700'
                                      : 'text-navy-700',
                            )
                        "
                    >
                        {{ check.label }}
                    </span>
                    <span
                        v-if="check.detail"
                        class="block text-[11px] text-navy-500"
                        >{{ check.detail }}</span
                    >
                </span>
            </li>
        </ul>

        <button
            v-if="canManage"
            type="button"
            :disabled="!build.canBuild || starting || build.busy"
            class="mt-4 inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white shadow-[0_8px_20px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500 disabled:pointer-events-none disabled:opacity-50"
            @click="buildPdf"
        >
            <LoaderCircle
                v-if="starting || build.busy"
                class="size-4 animate-spin"
            />
            <RefreshCw v-else-if="file" class="size-4" />
            <Sparkles v-else class="size-4" />
            {{ file ? t("Qayta yig'ish") : t("Son PDF ini yig'ish") }}
        </button>
        <p v-if="error" class="mt-2 text-xs text-red-600">{{ error }}</p>
        <p
            v-if="canManage && file && !build.auto"
            class="mt-2 text-[11px] text-navy-400"
        >
            {{ t("Qayta yig'ilsa, qo'lda yuklangan fayl almashtiriladi.") }}
        </p>
    </section>
</template>
