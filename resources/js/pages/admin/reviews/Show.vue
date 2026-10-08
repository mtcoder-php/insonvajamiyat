<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Download,
    ExternalLink,
    FileText,
    Hourglass,
    Lock,
    ShieldCheck,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ReviewForm from '@/components/admin/reviews/ReviewForm.vue';
import ReviewProcess from '@/components/admin/reviews/ReviewProcess.vue';
import { formatDate, formatDateTime, formatFileSize } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/reviews';
import type { LocaleCode, ReviewShowProps } from '@/types';
import { t, tk } from '@/lib/i18n';

/**
 * Taqriz sahifasi (admin reviewer.png): anonim maqola, PDF ko'rish, taqriz formasi.
 */
const props = defineProps<ReviewShowProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: tk('Admin panel'), href: dashboard() },
            { title: tk('Taqrizlarim'), href: index() },
            { title: tk('Maqola'), href: index() },
        ],
    },
});

type Tab = 'content' | 'files' | 'criteria' | 'history';

const tabs: { key: Tab; label: string }[] = [
    { key: 'content', label: t('Maqola mazmuni') },
    { key: 'files', label: t('Fayllar') },
    { key: 'criteria', label: t('Taqrizlash mezonlari') },
    { key: 'history', label: t('Tarix va jarayon') },
];

const tab = ref<Tab>('content');

const LANGUAGES: Record<LocaleCode, string> = {
    uz: t("O'zbekcha"),
    ru: 'Русский',
    en: 'English',
};

const article = computed(() => props.review.article);
const files = computed(() => article.value.files);
const pdf = computed(
    () => files.value.find((f) => f.extension === 'pdf') ?? null,
);
const locked = computed(
    () => !['accepted', 'completed'].includes(props.review.status),
);

const abstracts = computed(() =>
    (Object.keys(LANGUAGES) as LocaleCode[])
        .map((code) => ({
            code,
            label: LANGUAGES[code],
            title: article.value.titles[code],
            text: article.value.abstracts[code],
        }))
        .filter((a) => a.text),
);

const dueChip = computed(() => {
    const d = props.review.daysLeft;

    if (d === null) {
        return null;
    }

    return d < 0
        ? `Muddat ${Math.abs(d)} kun o'tdi`
        : `Taqriz muddati: ${d} kun qoldi`;
});

const statusTint: Record<string, string> = {
    invited: 'bg-amber-50 text-amber-700 ring-amber-200',
    accepted: 'bg-violet-50 text-violet-700 ring-violet-200',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    declined: 'bg-red-50 text-red-700 ring-red-200',
    cancelled: 'bg-navy-50 text-navy-500 ring-navy-200',
};

const criteriaHints: Record<string, string> = {
    relevance: t(
        "Mavzu fan va jamiyat uchun qanchalik dolzarb, muammo aniq qo'yilganmi.",
    ),
    novelty: t(
        'Ilmiy yangilik, mualliflik hissasi va mavjud tadqiqotlardan farqi.',
    ),
    methodology: t(
        "Tadqiqot usullari, ma'lumotlar va yondashuvning asoslanganligi.",
    ),
    results: t(
        'Natijalar ishonchliligi, tahlil chuqurligi va dalillar sifati.',
    ),
    conclusions: t(
        'Xulosalar natijalardan kelib chiqadimi, amaliy tavsiyalar bormi.',
    ),
    references: t("Manbalar soni, dolzarbligi va to'g'ri rasmiylashtirilishi."),
};

const history = computed(() =>
    [
        {
            at: props.review.invitedAt,
            text: `Taqriz taklifi yuborildi (${props.review.round}-raund)`,
        },
        {
            at: props.review.respondedAt,
            text:
                props.review.status === 'declined'
                    ? t('Taklif rad etildi')
                    : t('Taklif qabul qilindi'),
        },
        { at: props.review.completedAt, text: t('Taqriz topshirildi') },
    ].filter((e) => e.at),
);
</script>

<template>
    <Head :title="article.title" />

    <div class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 md:p-6">
        <div
            class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_360px] 2xl:grid-cols-[minmax(0,1fr)_400px]"
        >
            <div class="grid min-w-0 gap-5">
                <!-- Sarlavha -->
                <section
                    class="rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                >
                    <div
                        class="flex flex-col gap-5 2xl:flex-row 2xl:items-start"
                    >
                        <div class="min-w-0 flex-1">
                            <Link
                                :href="review.urls.index"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-line px-3 py-1.5 text-[13px] font-semibold text-navy-800 transition-all hover:-translate-x-0.5 hover:border-brand-300 hover:text-brand-700"
                            >
                                <ArrowLeft class="size-4" /> {{ t('Orqaga') }}
                            </Link>
                            <h1
                                class="mt-3 font-serif text-2xl leading-snug font-bold text-navy-950 md:text-[28px]"
                            >
                                {{ article.title }}
                            </h1>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span
                                    v-if="dueChip"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-200 ring-inset"
                                >
                                    <Hourglass class="size-3.5" /> {{ dueChip }}
                                </span>
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700 ring-1 ring-red-200 ring-inset"
                                >
                                    <ShieldCheck class="size-3.5" />
                                    {{
                                        t(
                                            "Blind Review (muallif ma'lumotlari ko'rsatilmaydi)",
                                        )
                                    }}
                                </span>
                            </div>
                        </div>
                        <dl
                            class="grid shrink-0 grid-cols-2 gap-x-6 gap-y-3 rounded-xl border border-line p-4 text-[13px] sm:grid-cols-4 2xl:w-80 2xl:grid-cols-2"
                        >
                            <div>
                                <dt class="text-[11px] text-navy-500">
                                    {{ t('Maqola ID') }}
                                </dt>
                                <dd class="font-bold text-navy-900">
                                    #IJ-{{ article.code }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-navy-500">
                                    {{ t('Qabul qilingan') }}
                                </dt>
                                <dd class="font-bold text-navy-900">
                                    {{ formatDate(article.submittedAt) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-navy-500">
                                    {{ t('Turi') }}
                                </dt>
                                <dd class="font-medium text-navy-900">
                                    {{ article.type }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-[11px] text-navy-500">
                                    {{ t('Holat') }}
                                </dt>
                                <dd>
                                    <span
                                        :class="
                                            cn(
                                                'inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset',
                                                statusTint[review.status],
                                            )
                                        "
                                    >
                                        {{ review.statusLabel }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div
                        class="mt-5 grid gap-4 rounded-xl bg-[#f5f8fc] px-4 py-3 text-[13px] sm:grid-cols-4"
                    >
                        <div>
                            <p class="text-[11px] text-navy-500">
                                {{ t('Muallif') }}
                            </p>
                            <p
                                class="flex items-center gap-1.5 font-semibold text-navy-800"
                            >
                                <Lock class="size-3.5 text-navy-400" />
                                {{ t('Anonim') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-[11px] text-navy-500">
                                {{ t("Yo'nalish") }}
                            </p>
                            <p class="font-semibold text-navy-800">
                                {{ article.subject ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-[11px] text-navy-500">
                                {{ t('Til') }}
                            </p>
                            <p class="font-semibold text-navy-800">
                                {{ LANGUAGES[article.language] }}
                            </p>
                        </div>
                        <div>
                            <p class="text-[11px] text-navy-500">
                                {{ t('Raund') }}
                            </p>
                            <p class="font-semibold text-navy-800">
                                {{
                                    t(':number-taqriz raundi', {
                                        number: review.round,
                                    })
                                }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Tablar -->
                <section
                    class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
                >
                    <div
                        class="flex gap-1 overflow-x-auto border-b border-line px-4"
                        role="tablist"
                    >
                        <button
                            v-for="item in tabs"
                            :key="item.key"
                            type="button"
                            role="tab"
                            :aria-selected="tab === item.key"
                            :class="
                                cn(
                                    'relative shrink-0 px-3 py-3.5 text-[13px] font-semibold transition-colors',
                                    tab === item.key
                                        ? 'text-brand-700 after:absolute after:inset-x-2 after:bottom-0 after:h-0.5 after:rounded-full after:bg-brand-600'
                                        : 'text-navy-600 hover:text-navy-900',
                                )
                            "
                            @click="tab = item.key"
                        >
                            {{ item.label }}
                            <span
                                v-if="item.key === 'files'"
                                class="text-navy-400"
                                >({{ files.length }})</span
                            >
                        </button>
                    </div>

                    <!-- Maqola mazmuni -->
                    <div
                        v-if="tab === 'content'"
                        class="grid gap-5 p-5 2xl:grid-cols-[minmax(0,320px)_minmax(0,1fr)]"
                    >
                        <div class="grid content-start gap-4">
                            <div
                                v-if="article.authorResponse?.note"
                                class="rounded-xl border border-orange-200 bg-orange-50/50 p-4 transition-shadow hover:shadow-[0_12px_26px_-20px_rgba(234,88,12,0.5)]"
                            >
                                <p
                                    class="text-[11px] font-semibold tracking-wide text-orange-700 uppercase"
                                >
                                    {{
                                        t('Muallif javobi · v:version', {
                                            version:
                                                article.authorResponse.version,
                                        })
                                    }}
                                </p>
                                <p
                                    class="mt-1.5 text-[13px] leading-relaxed whitespace-pre-line text-navy-800"
                                >
                                    {{ article.authorResponse.note }}
                                </p>
                                <p class="mt-2 text-[11px] text-navy-500">
                                    {{
                                        t(
                                            'Oldingi raund izohlariga javob. Tuzatilgan fayl «Fayllar» tabida birinchi turadi.',
                                        )
                                    }}
                                </p>
                            </div>
                            <div
                                v-for="item in abstracts"
                                :key="item.code"
                                class="rounded-xl border border-line p-4 transition-shadow hover:shadow-[0_12px_26px_-20px_rgba(0,36,66,0.5)]"
                            >
                                <p
                                    class="text-[11px] font-semibold tracking-wide text-brand-700 uppercase"
                                >
                                    {{ t('Annotatsiya') }} · {{ item.label }}
                                </p>
                                <p
                                    v-if="item.title"
                                    class="mt-1 text-[13px] font-semibold text-navy-900"
                                >
                                    {{ item.title }}
                                </p>
                                <p
                                    class="mt-1.5 text-[13px] leading-relaxed text-navy-700"
                                >
                                    {{ item.text }}
                                </p>
                            </div>
                            <div v-if="article.keywords.length">
                                <p
                                    class="mb-2 text-xs font-semibold text-navy-700"
                                >
                                    {{ t("Kalit so'zlar") }}
                                </p>
                                <div class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="word in article.keywords"
                                        :key="word"
                                        class="rounded-md border border-brand-100 bg-brand-50/60 px-2 py-0.5 text-[11px] font-medium text-brand-800"
                                    >
                                        {{ word }}
                                    </span>
                                </div>
                            </div>
                            <div
                                class="flex gap-3 rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 text-xs leading-relaxed text-emerald-900"
                            >
                                <Lock
                                    class="mt-0.5 size-4 shrink-0 text-emerald-600"
                                />
                                <span>
                                    <b class="block text-[13px]">{{
                                        t('Blind Review')
                                    }}</b>
                                    {{
                                        t(
                                            "Muallifning shaxsiy ma'lumotlari sizga ko'rsatilmaydi. Maqola anonim tarzda taqriz qilinadi.",
                                        )
                                    }}
                                </span>
                            </div>
                        </div>

                        <div class="min-w-0">
                            <div
                                v-if="locked"
                                class="flex h-full min-h-80 flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-navy-200 bg-[#f8fafd] p-8 text-center"
                            >
                                <span
                                    class="flex size-14 items-center justify-center rounded-full bg-white text-navy-400 shadow-sm"
                                >
                                    <Lock class="size-6" />
                                </span>
                                <p class="max-w-sm text-sm text-navy-600">
                                    {{
                                        t(
                                            "Maqola fayllari taklifni qabul qilganingizdan so'ng ochiladi.",
                                        )
                                    }}
                                </p>
                            </div>
                            <div
                                v-else-if="pdf"
                                class="overflow-hidden rounded-xl border border-line"
                            >
                                <div
                                    class="flex items-center gap-3 border-b border-line bg-[#f8fafd] px-3 py-2"
                                >
                                    <FileText
                                        class="size-5 shrink-0 text-red-500"
                                    />
                                    <span class="min-w-0 flex-1">
                                        <span
                                            class="block truncate text-[13px] font-semibold text-navy-900"
                                            >{{ pdf.name }}</span
                                        >
                                        <span
                                            class="text-[11px] text-navy-500"
                                            >{{
                                                formatFileSize(pdf.size)
                                            }}</span
                                        >
                                    </span>
                                    <a
                                        :href="pdf.downloadUrl"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 transition-colors hover:bg-brand-50"
                                    >
                                        <Download class="size-4" />
                                        <span class="hidden sm:inline">{{
                                            t('Yuklab olish')
                                        }}</span>
                                    </a>
                                    <a
                                        :href="pdf.viewUrl"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-navy-700 transition-colors hover:bg-navy-50"
                                    >
                                        <ExternalLink class="size-4" />
                                        <span class="hidden sm:inline">{{
                                            t("To'liq ekran")
                                        }}</span>
                                    </a>
                                </div>
                                <iframe
                                    :src="pdf.viewUrl"
                                    :title="pdf.name"
                                    class="h-[480px] w-full bg-[#eef2f8] sm:h-[720px]"
                                />
                            </div>
                            <div
                                v-else
                                class="flex min-h-60 flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-navy-200 p-8 text-center text-sm text-navy-600"
                            >
                                <FileText class="size-8 text-navy-300" />
                                PDF fayl yo'q. Qo'lyozmani "Fayllar" tabidan
                                yuklab oling.
                            </div>
                        </div>
                    </div>

                    <!-- Fayllar -->
                    <div v-else-if="tab === 'files'" class="p-5">
                        <p
                            v-if="locked"
                            class="py-8 text-center text-sm text-navy-500"
                        >
                            {{
                                t(
                                    'Fayllar taklifni qabul qilgandan keyin ochiladi.',
                                )
                            }}
                        </p>
                        <ul v-else class="grid gap-2">
                            <li
                                v-for="file in files"
                                :key="file.uuid"
                                class="flex items-center gap-3 rounded-lg border border-line px-4 py-3 transition-all hover:-translate-y-px hover:border-brand-200 hover:shadow-sm"
                            >
                                <span
                                    class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-[10px] font-bold text-brand-700 uppercase"
                                >
                                    {{ file.extension }}
                                </span>
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
                                    :href="file.downloadUrl"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-line px-3 py-1.5 text-xs font-semibold text-navy-800 transition-colors hover:border-brand-300 hover:text-brand-700"
                                >
                                    <Download class="size-4" />
                                    {{ t('Yuklab olish') }}
                                </a>
                            </li>
                            <li
                                v-if="!files.length"
                                class="py-8 text-center text-sm text-navy-500"
                            >
                                {{ t("Fayllar yo'q.") }}
                            </li>
                        </ul>
                    </div>

                    <!-- Mezonlar -->
                    <div
                        v-else-if="tab === 'criteria'"
                        class="grid gap-3 p-5 sm:grid-cols-2"
                    >
                        <div
                            v-for="(criterion, i) in options.criteria"
                            :key="criterion.key"
                            class="rounded-xl border border-line p-4 transition-shadow hover:shadow-[0_12px_26px_-20px_rgba(0,36,66,0.5)]"
                        >
                            <p class="text-[13px] font-bold text-navy-900">
                                {{ i + 1 }}. {{ criterion.label }}
                            </p>
                            <p
                                class="mt-1 text-xs leading-relaxed text-navy-600"
                            >
                                {{ criteriaHints[criterion.key] }}
                            </p>
                        </div>
                        <p class="text-xs text-navy-500 sm:col-span-2">
                            {{
                                t(
                                    "Har bir mezon 1 dan 5 gacha (0,5 qadam) baholanadi: 1 — juda past, 3 — qoniqarli, 5 — a'lo.",
                                )
                            }}
                        </p>
                    </div>

                    <!-- Tarix -->
                    <ol v-else class="grid gap-4 p-5">
                        <li
                            v-for="event in history"
                            :key="event.text"
                            class="flex gap-3"
                        >
                            <span
                                class="mt-1.5 size-2.5 shrink-0 rounded-full bg-brand-500 ring-4 ring-brand-100"
                            />
                            <span>
                                <span
                                    class="block text-[13px] font-semibold text-navy-900"
                                    >{{ event.text }}</span
                                >
                                <span class="text-[11px] text-navy-500">{{
                                    formatDateTime(event.at)
                                }}</span>
                            </span>
                        </li>
                    </ol>
                </section>

                <ReviewProcess :review="review" />
            </div>

            <ReviewForm
                :key="`${review.id}-${review.status}`"
                :review="review"
                :options="options"
            />
        </div>
    </div>
</template>
