<script setup lang="ts">
import {
    BookText,
    CalendarDays,
    Download,
    FileText,
    History,
    KeyRound,
    Layers,
    Lock,
    Star,
    UserRound,
    UsersRound,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import EditorialNotes from '@/components/admin/articles/EditorialNotes.vue';
import ReviewsPanel from '@/components/admin/articles/ReviewsPanel.vue';
import MessageThread from '@/components/articles/MessageThread.vue';
import ArticleStatusPill from '@/components/cabinet/ArticleStatusPill.vue';
import { formatDate, formatFileSize, formatTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { EditorialArticle, EditorialFile, LocaleCode } from '@/types';
import { t } from '@/lib/i18n';

/**
 * O'rta ustun: tanlangan maqola — asosiy ma'lumotlar, hujjatlar, taqrizchilar, jarayon.
 */
const props = defineProps<{ article: EditorialArticle }>();

const emit = defineEmits<{ invite: [] }>();

type Tab = 'main' | 'documents' | 'reviewers' | 'messages' | 'process';

const tabs: { key: Tab; label: string }[] = [
    { key: 'main', label: t("Asosiy ma'lumotlar") },
    { key: 'documents', label: t('Hujjatlar') },
    { key: 'reviewers', label: t('Taqrizchilar') },
    { key: 'messages', label: t('Yozishma') },
    { key: 'process', label: t('Jarayon') },
];

// Tab ota sahifadan boshqariladi ("Muallifga xabar yuborish" → Yozishma)
const tab = defineModel<Tab>('tab', { default: 'main' });

const languageLabels: Record<LocaleCode, string> = {
    uz: t("O'zbekcha"),
    ru: 'Русский',
    en: 'English',
};

const abstractLang = ref<LocaleCode>(props.article.language);

watch(
    () => props.article.uuid,
    () => {
        abstractLang.value = props.article.language;
    },
);

const abstractLanguages = computed(() =>
    (Object.keys(props.article.abstracts) as LocaleCode[]).filter(
        (code) => props.article.abstracts[code],
    ),
);

const keywords = computed(() =>
    (Object.keys(props.article.keywords) as LocaleCode[]).flatMap(
        (code) => props.article.keywords[code],
    ),
);

const fileTint = (file: EditorialFile): string =>
    file.extension === 'pdf'
        ? 'bg-red-50 text-red-600'
        : ['png', 'jpg', 'jpeg'].includes(file.extension)
          ? 'bg-emerald-50 text-emerald-600'
          : 'bg-brand-50 text-brand-700';
</script>

<template>
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
                        '-mb-px shrink-0 border-b-2 px-3 py-3 text-[13px] font-semibold whitespace-nowrap transition-colors',
                        tab === item.key
                            ? 'border-brand-600 text-brand-700'
                            : 'border-transparent text-navy-500 hover:text-navy-900',
                    )
                "
                @click="tab = item.key"
            >
                {{ item.label }}
                <span
                    v-if="item.key === 'messages' && article.messages.length"
                    class="ml-1 rounded-full bg-brand-50 px-1.5 text-[10px] text-brand-700"
                    >{{ article.messages.length }}</span
                >
            </button>
        </div>

        <div class="p-5">
            <!-- Sarlavha -->
            <header class="mb-5">
                <div class="flex items-start justify-between gap-3">
                    <h2
                        class="font-serif text-xl leading-snug font-bold text-navy-950"
                    >
                        {{ article.title }}
                    </h2>
                    <span
                        class="shrink-0 rounded-md bg-navy-50 px-2 py-1 font-mono text-[11px] font-semibold text-navy-600"
                    >
                        ID: {{ article.code }}
                    </span>
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <ArticleStatusPill
                        :group="article.statusGroup"
                        :label="article.statusLabel"
                    />
                    <span
                        v-if="article.reviewRound > 0"
                        class="text-xs text-navy-500"
                        >{{
                            t(':number-taqriz bosqichi', {
                                number: article.reviewRound,
                            })
                        }}</span
                    >
                </div>
                <div class="mt-3 flex items-center gap-2.5">
                    <span
                        class="flex size-9 items-center justify-center rounded-full bg-navy-950 text-white"
                    >
                        <UserRound class="size-4" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-[13px] font-semibold text-navy-900">
                            {{ article.submitter.name }}
                        </p>
                        <p class="truncate text-xs text-navy-500">
                            {{
                                article.submitter.organization ??
                                article.submitter.email
                            }}
                        </p>
                    </div>
                </div>
                <dl
                    class="mt-3 flex flex-wrap gap-x-5 gap-y-1.5 text-xs text-navy-600"
                >
                    <div class="flex items-center gap-1.5">
                        <FileText class="size-3.5 text-navy-400" />
                        <dt>{{ t('Tur:') }}</dt>
                        <dd class="font-medium text-navy-800">
                            {{ article.type }}
                        </dd>
                    </div>
                    <div
                        v-if="article.subject"
                        class="flex items-center gap-1.5"
                    >
                        <Layers class="size-3.5 text-navy-400" />
                        <dt>{{ t("Yo'nalish:") }}</dt>
                        <dd class="font-medium text-navy-800">
                            {{ article.subject }}
                        </dd>
                    </div>
                    <div v-if="article.issue" class="flex items-center gap-1.5">
                        <BookText class="size-3.5 text-navy-400" />
                        <dt>{{ t('Jurnal soni:') }}</dt>
                        <dd class="font-medium text-navy-800">
                            {{ article.issue }}
                        </dd>
                    </div>
                    <div
                        v-if="article.submittedAt"
                        class="flex items-center gap-1.5"
                    >
                        <CalendarDays class="size-3.5 text-navy-400" />
                        <dt>{{ t('Sana:') }}</dt>
                        <dd class="font-medium text-navy-800 tabular-nums">
                            {{ formatDate(article.submittedAt) }}
                        </dd>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <dt>{{ t("To'lov:") }}</dt>
                        <dd class="font-medium text-navy-800">
                            {{ article.paymentStatusLabel }}
                        </dd>
                    </div>
                </dl>
            </header>

            <!-- Asosiy ma'lumotlar -->
            <div v-show="tab === 'main'" class="grid gap-6">
                <section>
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <h3
                            class="flex items-center gap-2 text-[13px] font-bold text-navy-950"
                        >
                            <BookText class="size-4 text-brand-600" />
                            {{ t('Annotatsiya') }}
                        </h3>
                        <div
                            v-if="abstractLanguages.length > 1"
                            class="flex gap-1 rounded-lg bg-[#f5f8fc] p-0.5"
                        >
                            <button
                                v-for="code in abstractLanguages"
                                :key="code"
                                type="button"
                                :class="
                                    cn(
                                        'rounded-md px-2 py-0.5 text-[11px] font-semibold uppercase transition-colors',
                                        abstractLang === code
                                            ? 'bg-white text-brand-700 shadow-sm'
                                            : 'text-navy-500 hover:text-navy-800',
                                    )
                                "
                                :title="languageLabels[code]"
                                @click="abstractLang = code"
                            >
                                {{ code }}
                            </button>
                        </div>
                    </div>
                    <p
                        v-if="
                            abstractLang !== article.language &&
                            article.titles[abstractLang]
                        "
                        class="mb-1 text-[13px] font-semibold text-navy-800"
                    >
                        {{ article.titles[abstractLang] }}
                    </p>
                    <p
                        class="text-[13px] leading-relaxed whitespace-pre-line text-navy-700"
                    >
                        {{ article.abstracts[abstractLang] ?? '—' }}
                    </p>
                </section>

                <section v-if="keywords.length">
                    <h3
                        class="mb-2 flex items-center gap-2 text-[13px] font-bold text-navy-950"
                    >
                        <KeyRound class="size-4 text-brand-600" />
                        {{ t("Kalit so'zlar") }}
                    </h3>
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="word in keywords"
                            :key="word"
                            class="rounded-md bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-700 ring-1 ring-brand-100 ring-inset"
                        >
                            {{ word }}
                        </span>
                    </div>
                </section>

                <section>
                    <h3
                        class="mb-2 flex items-center gap-2 text-[13px] font-bold text-navy-950"
                    >
                        <UsersRound class="size-4 text-brand-600" />
                        {{ t('Mualliflar') }}
                    </h3>
                    <ul class="grid gap-1.5 text-[13px]">
                        <li
                            v-for="(author, i) in article.authors"
                            :key="i"
                            class="flex flex-wrap items-baseline gap-x-2"
                        >
                            <span class="font-semibold text-navy-900">
                                {{ i + 1 }}. {{ author.name }}
                            </span>
                            <Star
                                v-if="author.isCorresponding"
                                class="size-3.5 self-center fill-gold-400 text-gold-500"
                                :aria-label="t('Aloqa uchun mas\'ul')"
                            />
                            <span class="text-xs text-navy-500">
                                {{
                                    [
                                        author.degree,
                                        author.organization,
                                        author.email,
                                    ]
                                        .filter(Boolean)
                                        .join(' · ')
                                }}
                            </span>
                        </li>
                    </ul>
                </section>

                <section>
                    <h3
                        class="mb-2 flex items-center gap-2 text-[13px] font-bold text-navy-950"
                    >
                        <FileText class="size-4 text-brand-600" />
                        {{ t('Fayllar') }}
                    </h3>
                    <ul
                        v-if="article.files.length"
                        class="divide-y divide-line rounded-lg border border-line"
                    >
                        <li v-for="file in article.files" :key="file.uuid">
                            <a
                                :href="file.url"
                                class="group flex items-center gap-3 px-3 py-2.5 text-[13px] transition-colors hover:bg-brand-50/50"
                            >
                                <span
                                    :class="
                                        cn(
                                            'flex size-8 shrink-0 items-center justify-center rounded-md text-[10px] font-bold uppercase',
                                            fileTint(file),
                                        )
                                    "
                                >
                                    {{ file.extension }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block truncate font-medium text-navy-900 group-hover:text-brand-700"
                                    >
                                        {{ file.name }}
                                    </span>
                                    <span class="text-[11px] text-navy-500">{{
                                        file.typeLabel
                                    }}</span>
                                </span>
                                <span
                                    class="hidden text-xs text-navy-500 tabular-nums sm:block"
                                >
                                    {{ formatFileSize(file.size) }}
                                </span>
                                <span
                                    class="hidden text-xs text-navy-400 tabular-nums md:block"
                                >
                                    {{ formatDate(file.uploadedAt) }}
                                </span>
                                <Download
                                    class="size-4 text-navy-400 transition-transform group-hover:translate-y-0.5 group-hover:text-brand-600"
                                />
                            </a>
                        </li>
                    </ul>
                    <p v-else class="text-[13px] text-navy-500">
                        {{ t("Fayllar yo'q") }}
                    </p>
                </section>

                <EditorialNotes
                    :notes="article.notes"
                    :url="article.urls.notes"
                    reload="selected"
                />
            </div>

            <!-- Hujjatlar: versiyalar -->
            <div v-show="tab === 'documents'" class="grid gap-4">
                <article
                    v-for="version in article.versions"
                    :key="version.number"
                    class="rounded-lg border border-line p-4"
                >
                    <header
                        class="mb-2 flex flex-wrap items-baseline justify-between gap-2"
                    >
                        <h3 class="text-[13px] font-bold text-navy-950">
                            {{
                                t(':number-versiya', { number: version.number })
                            }}
                            ·
                            {{ version.type }}
                        </h3>
                        <span class="text-xs text-navy-500 tabular-nums">
                            {{ formatDate(version.createdAt) }}
                            {{ formatTime(version.createdAt) }}
                        </span>
                    </header>
                    <p
                        v-if="version.note"
                        class="mb-2 text-[13px] text-navy-700"
                    >
                        {{ version.note }}
                    </p>
                    <ul class="grid gap-1">
                        <li v-for="file in version.files" :key="file.uuid">
                            <a
                                :href="file.url"
                                class="inline-flex items-center gap-2 text-[13px] text-brand-700 hover:underline"
                            >
                                <Download class="size-3.5" />
                                {{ file.name }}
                                <span class="text-xs text-navy-400"
                                    >({{ formatFileSize(file.size) }})</span
                                >
                            </a>
                        </li>
                    </ul>
                </article>
                <p
                    v-if="!article.versions.length"
                    class="py-6 text-center text-sm text-navy-500"
                >
                    {{ t("Versiyalar yo'q") }}
                </p>
                <section
                    v-if="article.references"
                    class="rounded-lg border border-line p-4"
                >
                    <h3 class="mb-2 text-[13px] font-bold text-navy-950">
                        {{ t("Adabiyotlar ro'yxati") }}
                    </h3>
                    <p
                        class="font-mono text-xs leading-relaxed whitespace-pre-line text-navy-700"
                    >
                        {{ article.references }}
                    </p>
                </section>
            </div>

            <!-- Taqrizchilar -->
            <div v-show="tab === 'reviewers'">
                <ReviewsPanel :article="article" @invite="emit('invite')" />
            </div>

            <!-- Yozishma -->
            <div v-show="tab === 'messages'">
                <p class="mb-3 text-xs text-navy-500">
                    {{
                        t(
                            "Muallif bilan yozishma. Muallif xodim ismini emas, «Tahririyat» yozuvini ko'radi; yangi xabar unga email orqali ham yuboriladi.",
                        )
                    }}
                </p>
                <MessageThread
                    :messages="article.messages"
                    :send-url="
                        article.can.message ? article.urls.message : null
                    "
                    :empty-text="t('Muallif bilan yozishma hali boshlanmagan.')"
                    :placeholder="t('Muallifga xabar...')"
                />
            </div>

            <!-- Jarayon -->
            <div v-show="tab === 'process'">
                <h3
                    class="mb-3 flex items-center gap-2 text-[13px] font-bold text-navy-950"
                >
                    <History class="size-4 text-brand-600" />
                    {{ t('Holat tarixi') }}
                </h3>
                <ol class="relative grid gap-4 border-l-2 border-line pl-5">
                    <li
                        v-for="item in article.history"
                        :key="item.id"
                        class="relative"
                    >
                        <span
                            class="absolute top-1.5 -left-[27px] size-3 rounded-full bg-brand-500 ring-4 ring-white"
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
                            <span
                                v-if="item.actor"
                                class="text-[11px] text-navy-500"
                                >· {{ item.actor }}</span
                            >
                            <span
                                v-if="!item.visibleToAuthor"
                                class="inline-flex items-center gap-1 rounded bg-navy-50 px-1.5 text-[10px] text-navy-500"
                            >
                                <Lock class="size-3" /> {{ t('ichki') }}
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

                <template v-if="article.decisions.length">
                    <h3 class="mt-6 mb-3 text-[13px] font-bold text-navy-950">
                        {{ t('Muharrir qarorlari') }}
                    </h3>
                    <ul class="grid gap-2">
                        <li
                            v-for="decision in article.decisions"
                            :key="decision.id"
                            class="rounded-lg border border-line p-3 text-[13px]"
                        >
                            <p
                                class="flex flex-wrap items-baseline justify-between gap-2"
                            >
                                <span class="font-semibold text-navy-900">
                                    {{ decision.label }}
                                    <span class="font-normal text-navy-500"
                                        >· {{ decision.editor }}</span
                                    >
                                </span>
                                <span
                                    class="text-[11px] text-navy-400 tabular-nums"
                                >
                                    {{ formatDate(decision.createdAt) }}
                                    {{ formatTime(decision.createdAt) }}
                                </span>
                            </p>
                            <p
                                v-if="decision.internalNote"
                                class="mt-1 text-xs text-navy-500"
                            >
                                <Lock class="mr-1 inline size-3" />{{
                                    decision.internalNote
                                }}
                            </p>
                        </li>
                    </ul>
                </template>
            </div>
        </div>
    </section>
</template>
