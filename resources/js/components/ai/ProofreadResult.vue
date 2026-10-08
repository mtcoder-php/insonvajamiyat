<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Check,
    CheckCheck,
    Copy,
    FileDown,
    LoaderCircle,
    Save,
    Undo2,
    X,
} from '@lucide/vue';
import { computed, nextTick, reactive, ref, watch } from 'vue';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { AiRequestDetail, ProofDecision, ProofIssue } from '@/types';
import ScoreRing from './ScoreRing.vue';
import { copyText, issueMeta, qualityLabel } from './aiMeta';
import { t } from '@/lib/i18n';

/**
 * Proofreader natijasi: asl matn (xatolar belgilangan) ↔ tuzatilgan matn,
 * takliflarni qabul/rad etish va yakuniy matnni saqlash.
 * Hech bir taklif avtomatik qo'llanmaydi — faqat qabul qilinganlari.
 */
const props = defineProps<{ request: AiRequestDetail }>();

const result = computed(() => props.request.proofread!);
const decisions = reactive<Record<string, ProofDecision>>({});

function syncDecisions(): void {
    for (const key of Object.keys(decisions)) {
        delete decisions[key];
    }

    for (const issue of result.value.issues) {
        decisions[issue.id] = issue.status;
    }
}

syncDecisions();
watch(() => props.request.uuid, syncDecisions);

const byId = computed(
    () => new Map(result.value.issues.map((issue) => [issue.id, issue])),
);

const finalText = computed(() =>
    result.value.segments
        .map((segment) => {
            const issue = segment.issue ? byId.value.get(segment.issue) : null;

            return issue && decisions[issue.id] === 'accepted'
                ? issue.suggestion
                : segment.text;
        })
        .join(''),
);

const counts = computed(() => {
    const values = Object.values(decisions);

    return {
        accepted: values.filter((v) => v === 'accepted').length,
        rejected: values.filter((v) => v === 'rejected').length,
        pending: values.filter((v) => v === 'pending').length,
    };
});

const dirty = computed(() =>
    result.value.issues.some((issue) => decisions[issue.id] !== issue.status),
);

const active = ref<string | null>(null);
const saving = ref(false);
const copied = ref(false);
const view = ref<'split' | 'original' | 'corrected'>('split');

function decide(issue: ProofIssue, value: ProofDecision): void {
    decisions[issue.id] = decisions[issue.id] === value ? 'pending' : value;
}

function decideAll(value: ProofDecision): void {
    for (const issue of result.value.issues) {
        decisions[issue.id] = value;
    }
}

async function focus(id: string | null): Promise<void> {
    if (!id) {
        return;
    }

    active.value = id;
    await nextTick();
    document
        .getElementById(`issue-${id}`)
        ?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function save(): void {
    saving.value = true;
    router.put(
        result.value.saveUrl,
        { decisions: { ...decisions } },
        {
            preserveScroll: true,
            only: ['current'],
            onFinish: () => (saving.value = false),
        },
    );
}

async function copy(): Promise<void> {
    copied.value = await copyText(finalText.value);
    setTimeout(() => (copied.value = false), 1600);
}

function markClass(issueId: string): string {
    const issue = byId.value.get(issueId);

    if (!issue) {
        return '';
    }

    const decision = decisions[issueId];

    return cn(
        'cursor-pointer rounded px-0.5 underline decoration-2 underline-offset-[3px] transition-colors',
        decision === 'rejected'
            ? 'bg-transparent text-navy-700 decoration-navy-300 decoration-dotted'
            : decision === 'accepted'
              ? 'bg-red-50 text-red-600 line-through decoration-red-400'
              : issueMeta[issue.type].mark,
        active.value === issueId && 'ring-2 ring-brand-400 ring-offset-1',
    );
}
</script>

<template>
    <div class="grid grid-cols-1 gap-4">
        <!-- Tahlil natijasi -->
        <section
            class="flex flex-wrap items-center gap-x-6 gap-y-4 rounded-xl border border-line bg-white p-4 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <div class="flex items-center gap-3">
                <ScoreRing :value="result.score" :size="68" />
                <div>
                    <p class="text-xs text-navy-500">{{ t('Matn sifati') }}</p>
                    <p class="font-sans text-[15px] font-bold text-navy-950">
                        {{ qualityLabel(result.score) }}
                    </p>
                </div>
            </div>
            <dl class="flex flex-wrap gap-x-5 gap-y-2 text-[13px]">
                <div class="flex items-center gap-2">
                    <span class="size-2 rounded-full bg-red-500" />
                    <dt class="text-navy-600">{{ t('Xatolar') }}</dt>
                    <dd class="font-semibold tabular-nums">
                        {{ result.counts.errors }}
                    </dd>
                </div>
                <div class="flex items-center gap-2">
                    <span class="size-2 rounded-full bg-amber-500" />
                    <dt class="text-navy-600">{{ t('Takliflar') }}</dt>
                    <dd class="font-semibold tabular-nums">
                        {{ result.counts.suggestions }}
                    </dd>
                </div>
                <div class="flex items-center gap-2">
                    <span class="size-2 rounded-full bg-emerald-500" />
                    <dt class="text-navy-600">{{ t('Qabul qilingan') }}</dt>
                    <dd class="font-semibold tabular-nums">
                        {{ counts.accepted }}
                    </dd>
                </div>
            </dl>
            <div class="ml-auto flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-line bg-white px-3.5 text-[13px] font-semibold text-navy-700 transition-all hover:-translate-y-px hover:border-brand-300 hover:text-brand-700"
                    @click="copy"
                >
                    <Check v-if="copied" class="size-4 text-emerald-600" />
                    <Copy v-else class="size-4" />
                    {{ copied ? t('Nusxalandi') : t('Nusxalash') }}
                </button>
                <a
                    :href="result.downloadUrl"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-line bg-white px-3.5 text-[13px] font-semibold text-navy-700 transition-all hover:-translate-y-px hover:border-brand-300 hover:text-brand-700"
                    :title="t('Qabul qilingan takliflar bilan')"
                >
                    <FileDown class="size-4" /> {{ t('Word') }}
                </a>
                <button
                    v-if="request.own"
                    type="button"
                    :disabled="saving || (!dirty && !!result.savedAt)"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white shadow-[0_8px_20px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500 disabled:pointer-events-none disabled:opacity-60"
                    @click="save"
                >
                    <LoaderCircle v-if="saving" class="size-4 animate-spin" />
                    <Save v-else class="size-4" />
                    {{ t('Yangi versiyani saqlash') }}
                </button>
            </div>
            <p
                v-if="result.summary || result.savedAt"
                class="w-full rounded-lg bg-[#f6f9fd] px-3 py-2 text-xs leading-relaxed text-navy-600"
            >
                {{ result.summary }}
                <span v-if="result.savedAt" class="text-navy-400">
                    ·
                    {{
                        t('Saqlangan: :date', {
                            date: formatDateTime(result.savedAt),
                        })
                    }}</span
                >
            </p>
        </section>

        <!-- Matnlar -->
        <div
            class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b border-line bg-[#fafcff] px-4 py-2.5"
            >
                <div
                    class="inline-flex rounded-lg bg-[#eef3fa] p-1 text-xs font-semibold"
                >
                    <button
                        v-for="option in [
                            { key: 'split', label: t('Yonma-yon') },
                            { key: 'original', label: t('Asl matn') },
                            { key: 'corrected', label: t('Tahrirlangan matn') },
                        ] as const"
                        :key="option.key"
                        type="button"
                        :class="
                            cn(
                                'rounded-md px-3 py-1.5 transition-all',
                                view === option.key
                                    ? 'bg-white text-brand-700 shadow-sm'
                                    : 'text-navy-500 hover:text-navy-800',
                            )
                        "
                        @click="view = option.key"
                    >
                        {{ option.label }}
                    </button>
                </div>
                <p class="text-xs text-navy-500">
                    {{ t('Xatolar:') }}
                    <b class="text-navy-900">{{ result.counts.errors }}</b>
                    {{ t('· Takliflar:') }}
                    <b class="text-navy-900">{{ result.counts.suggestions }}</b>
                </p>
            </div>

            <div
                :class="
                    cn(
                        'grid max-h-[28rem] overflow-y-auto',
                        view === 'split' &&
                            'lg:grid-cols-2 lg:divide-x lg:divide-line',
                    )
                "
            >
                <div
                    v-if="view !== 'corrected'"
                    class="px-5 py-4 text-[13.5px] leading-7 whitespace-pre-wrap text-navy-800"
                >
                    <p
                        v-if="view === 'split'"
                        class="mb-2 text-[11px] font-semibold tracking-wide text-navy-400 uppercase"
                    >
                        {{ t('Asl matn') }}
                    </p>
                    <template
                        v-for="(segment, index) in result.segments"
                        :key="index"
                        ><span
                            v-if="segment.issue"
                            :class="markClass(segment.issue)"
                            :title="byId.get(segment.issue)?.reason"
                            @click="focus(segment.issue)"
                            >{{ segment.text }}</span
                        ><template v-else>{{
                            segment.text
                        }}</template></template
                    >
                </div>
                <div
                    v-if="view !== 'original'"
                    class="px-5 py-4 text-[13.5px] leading-7 whitespace-pre-wrap text-navy-800"
                >
                    <p
                        v-if="view === 'split'"
                        class="mb-2 text-[11px] font-semibold tracking-wide text-navy-400 uppercase"
                    >
                        {{ t('Tahrirlangan matn') }}
                    </p>
                    <template
                        v-for="(segment, index) in result.segments"
                        :key="index"
                        ><span
                            v-if="
                                segment.issue &&
                                decisions[segment.issue] === 'accepted'
                            "
                            class="cursor-pointer rounded bg-emerald-50 px-0.5 font-medium text-emerald-700 underline decoration-emerald-400 decoration-2 underline-offset-[3px]"
                            @click="focus(segment.issue)"
                            >{{ byId.get(segment.issue)?.suggestion }}</span
                        ><span
                            v-else-if="
                                segment.issue &&
                                decisions[segment.issue] === 'pending'
                            "
                            class="cursor-pointer rounded underline decoration-amber-400 decoration-dashed decoration-2 underline-offset-[3px]"
                            @click="focus(segment.issue)"
                            >{{ segment.text }}</span
                        ><template v-else>{{
                            segment.text
                        }}</template></template
                    >
                </div>
            </div>
        </div>

        <!-- Takliflar -->
        <section
            class="rounded-xl border border-line bg-white p-4 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <header
                class="mb-3 flex flex-wrap items-center justify-between gap-2"
            >
                <h3 class="font-sans text-[14px] font-bold text-navy-950">
                    {{ t("Sun'iy intellekt takliflari") }}
                    <span class="ml-1 text-xs font-medium text-navy-400"
                        >({{ result.issues.length }})</span
                    >
                </h3>
                <div
                    v-if="result.issues.length && request.own"
                    class="flex gap-1.5"
                >
                    <button
                        type="button"
                        class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 text-xs font-semibold whitespace-nowrap text-emerald-700 transition-colors hover:bg-emerald-100"
                        @click="decideAll('accepted')"
                    >
                        <CheckCheck class="size-3.5" />
                        {{ t('Hammasini qabul qilish') }}
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-8 items-center gap-1.5 rounded-lg px-2.5 text-xs font-semibold whitespace-nowrap text-navy-500 transition-colors hover:bg-navy-50 hover:text-navy-800"
                        @click="decideAll('pending')"
                    >
                        <Undo2 class="size-3.5" /> {{ t('Tozalash') }}
                    </button>
                </div>
            </header>

            <p
                v-if="!result.issues.length"
                class="rounded-lg bg-emerald-50 px-4 py-6 text-center text-sm text-emerald-700"
            >
                {{ t('Xato topilmadi — matn toza.') }}
            </p>

            <ul v-else class="grid max-h-[24rem] gap-2 overflow-y-auto pr-1">
                <li
                    v-for="issue in result.issues"
                    :id="`issue-${issue.id}`"
                    :key="issue.id"
                    :class="
                        cn(
                            'group flex flex-wrap items-start gap-x-3 gap-y-2 rounded-lg border px-3 py-2.5 transition-all',
                            active === issue.id
                                ? 'border-brand-300 bg-brand-50/50 shadow-sm'
                                : 'border-line hover:border-brand-200 hover:bg-[#fafcff]',
                            decisions[issue.id] === 'rejected' && 'opacity-60',
                        )
                    "
                    @mouseenter="active = issue.id"
                >
                    <span
                        :class="
                            cn(
                                'mt-1.5 size-2.5 shrink-0 rounded-full ring-4 ring-white',
                                issueMeta[issue.type].dot,
                            )
                        "
                    />
                    <div class="min-w-[11rem] flex-1">
                        <p class="text-[13px] leading-snug break-words">
                            <span
                                class="text-red-600 line-through decoration-red-300"
                                >{{ issue.original }}</span
                            >
                            <span class="mx-1.5 text-navy-300">→</span>
                            <span class="font-semibold text-emerald-700">{{
                                issue.suggestion
                            }}</span>
                        </p>
                        <p class="mt-0.5 text-[11px] text-navy-500">
                            {{ issueMeta[issue.type].label
                            }}<template
                                v-if="
                                    issue.reason &&
                                    issue.reason !== issueMeta[issue.type].label
                                "
                            >
                                · {{ issue.reason }}</template
                            >
                        </p>
                    </div>
                    <div v-if="request.own" class="ml-auto flex shrink-0 gap-1">
                        <button
                            type="button"
                            :class="
                                cn(
                                    'inline-flex h-7 items-center gap-1 rounded-md px-2 text-[11px] font-semibold transition-all',
                                    decisions[issue.id] === 'accepted'
                                        ? 'bg-emerald-600 text-white shadow-sm'
                                        : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100',
                                )
                            "
                            :aria-pressed="decisions[issue.id] === 'accepted'"
                            @click="decide(issue, 'accepted')"
                        >
                            <Check class="size-3.5" /> {{ t('Qabul') }}
                        </button>
                        <button
                            type="button"
                            :class="
                                cn(
                                    'inline-flex h-7 items-center gap-1 rounded-md px-2 text-[11px] font-semibold transition-all',
                                    decisions[issue.id] === 'rejected'
                                        ? 'bg-red-600 text-white shadow-sm'
                                        : 'bg-red-50 text-red-600 hover:bg-red-100',
                                )
                            "
                            :aria-pressed="decisions[issue.id] === 'rejected'"
                            @click="decide(issue, 'rejected')"
                        >
                            <X class="size-3.5" /> {{ t('Rad') }}
                        </button>
                    </div>
                </li>
            </ul>
        </section>
    </div>
</template>
