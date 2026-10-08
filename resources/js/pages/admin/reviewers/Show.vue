<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    AlarmClock,
    ArrowLeft,
    CircleCheck,
    CirclePause,
    CirclePlay,
    ClipboardList,
    FolderTree,
    NotebookPen,
    PenTool,
    ShieldCheck,
    Star,
    Timer,
    UserMinus,
    UserRoundPlus,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import MetricTile from '@/components/admin/people/MetricTile.vue';
import PersonHeader from '@/components/admin/people/PersonHeader.vue';
import ProfileFacts from '@/components/admin/people/ProfileFacts.vue';
import SubjectPicker from '@/components/admin/people/SubjectPicker.vue';
import DeleteDialog from '@/components/admin/settings/DeleteDialog.vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import SectionCard from '@/components/admin/ui/SectionCard.vue';
import { formatDate, formatNumber } from '@/lib/format';
import { primaryButtonClass, secondaryButtonClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/reviewers';
import type { ReviewerHistoryItem, ReviewerShowProps } from '@/types';
import { t, tk } from '@/lib/i18n';

/**
 * Taqrizchi sahifasi: profil, ko'rsatkichlar, tavsiyalar taqsimoti va taqrizlar tarixi;
 * to'xtatish / faollashtirish, yo'nalishlar va taqrizchilikdan chiqarish.
 */
const props = defineProps<ReviewerShowProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: tk('Admin panel'), href: dashboard() },
            { title: tk('Taqrizchilar'), href: index() },
            { title: tk('Taqrizchi') },
        ],
    },
});

/* ---------- Amallar ---------- */

const pauseOpen = ref(false);
const pausing = ref(false);

function togglePause(): void {
    pausing.value = true;
    router.put(
        props.urls.status,
        { paused: !props.isPaused },
        {
            preserveScroll: true,
            onSuccess: () => (pauseOpen.value = false),
            onFinish: () => (pausing.value = false),
        },
    );
}

const subjectsOpen = ref(false);
const subjectsForm = useForm<{ subject_ids: number[] }>({
    subject_ids: props.subjects.map((s) => s.id),
});

function openSubjects(): void {
    subjectsForm.defaults({ subject_ids: props.subjects.map((s) => s.id) });
    subjectsForm.reset();
    subjectsForm.clearErrors();
    subjectsOpen.value = true;
}

function saveSubjects(): void {
    subjectsForm.put(props.urls.subjects, {
        preserveScroll: true,
        onSuccess: () => (subjectsOpen.value = false),
    });
}

const removeOpen = ref(false);
const restoring = ref(false);

function restoreReviewer(): void {
    restoring.value = true;
    router.post(
        props.urls.store,
        {
            user_id: props.profile.id,
            subject_ids: props.subjects.map((s) => s.id),
        },
        { onFinish: () => (restoring.value = false) },
    );
}

/* ---------- Ko'rsatkichlar ---------- */

const tiles = computed(() => [
    {
        key: 'active',
        label: t('Faol taqrizlar'),
        value: formatNumber(props.stats.active),
        hint: props.stats.overdue
            ? `${props.stats.overdue} tasi muddati o'tgan`
            : `Band chegarasi: ${props.busyFrom}`,
        icon: AlarmClock,
        tint: props.stats.overdue
            ? 'bg-red-50 text-red-600'
            : 'bg-amber-50 text-amber-600',
    },
    {
        key: 'completed',
        label: t('Yakunlangan'),
        value: formatNumber(props.stats.completed),
        hint: t(':invited ta taklifdan', { invited: props.stats.invited }),
        icon: CircleCheck,
        tint: 'bg-emerald-50 text-emerald-600',
    },
    {
        key: 'speed',
        label: t("O'rtacha muddat"),
        value:
            props.stats.avgDays === null ? '—' : `${props.stats.avgDays} kun`,
        hint:
            props.stats.onTime === null
                ? t("Hali xulosa yo'q")
                : `${props.stats.onTime}% muddatida`,
        icon: Timer,
        tint: 'bg-violet-50 text-violet-600',
    },
    {
        key: 'score',
        label: t("O'rtacha baho"),
        value:
            props.stats.avgScore === null ? '—' : `${props.stats.avgScore} / 5`,
        hint:
            props.stats.acceptRate === null
                ? undefined
                : `Takliflarni qabul qilish: ${props.stats.acceptRate}%`,
        icon: Star,
        tint: 'bg-gold-100 text-gold-700',
    },
]);

const recommendationMeta: Record<string, { label: string; bar: string }> = {
    accept: { label: t('Qabul qilish'), bar: 'bg-emerald-500' },
    minor_revision: { label: t('Kichik tuzatish'), bar: 'bg-sky-500' },
    major_revision: { label: t('Jiddiy tuzatish'), bar: 'bg-amber-500' },
    reject: { label: t('Rad etish'), bar: 'bg-red-500' },
};

const recommendationTotal = computed(() =>
    Object.values(props.stats.recommendations).reduce((a, b) => a + b, 0),
);

/* ---------- Taqrizlar tarixi ---------- */

const filters = [
    { value: '', label: t('Hammasi') },
    { value: 'active', label: t('Faol') },
    { value: 'completed', label: t('Yakunlangan') },
    { value: 'declined', label: t('Rad etilgan') },
    { value: 'cancelled', label: t('Bekor qilingan') },
] as const;

const filter = ref<(typeof filters)[number]['value']>('');

const visible = computed(() =>
    props.reviews.filter((r) => {
        if (!filter.value) {
            return true;
        }

        return filter.value === 'active'
            ? r.status === 'invited' || r.status === 'accepted'
            : r.status === filter.value;
    }),
);

const statusTone: Record<ReviewerHistoryItem['status'], string> = {
    invited: 'bg-brand-50 text-brand-700 ring-brand-200',
    accepted: 'bg-sky-50 text-sky-700 ring-sky-200',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    declined: 'bg-slate-100 text-slate-600 ring-slate-200',
    cancelled: 'bg-slate-100 text-slate-500 ring-slate-200',
};

const dangerButton =
    'inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-red-200 bg-white px-4 text-sm font-semibold text-red-600 transition-all hover:-translate-y-px hover:bg-red-50';
</script>

<template>
    <Head :title="profile.name" />

    <div
        class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 text-navy-900 md:p-6"
    >
        <Link
            :href="urls.index"
            class="group inline-flex w-fit items-center gap-1.5 text-xs font-semibold text-navy-500 hover:text-brand-700"
        >
            <ArrowLeft
                class="size-3.5 transition-transform group-hover:-translate-x-0.5"
            />
            {{ t("Taqrizchilar ro'yxati") }}
        </Link>

        <PersonHeader :profile="profile">
            <template #badges>
                <span
                    v-if="isReviewer"
                    class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-amber-200 ring-inset"
                >
                    <NotebookPen class="size-3" /> {{ t('Taqrizchi') }}
                </span>
                <span
                    v-else
                    class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-200 ring-inset"
                >
                    {{ t('Taqrizchilar bazasida emas') }}
                </span>
                <span
                    v-if="isReviewer"
                    :class="
                        cn(
                            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset',
                            isPaused
                                ? 'bg-slate-100 text-slate-600 ring-slate-200'
                                : 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                        )
                    "
                >
                    <CirclePause v-if="isPaused" class="size-3" />
                    <CirclePlay v-else class="size-3" />
                    {{
                        isPaused
                            ? t("To'xtatilgan · :date", {
                                  date: formatDate(pausedAt),
                              })
                            : t('Taklif qabul qiladi')
                    }}
                </span>
            </template>
            <template #actions>
                <template v-if="isReviewer">
                    <button
                        type="button"
                        :class="
                            isPaused ? primaryButtonClass : secondaryButtonClass
                        "
                        @click="pauseOpen = true"
                    >
                        <CirclePlay v-if="isPaused" class="size-4" />
                        <CirclePause v-else class="size-4" />
                        {{ isPaused ? t('Faollashtirish') : "To'xtatish" }}
                    </button>
                    <button
                        type="button"
                        :class="secondaryButtonClass"
                        @click="openSubjects"
                    >
                        <FolderTree class="size-4" /> {{ t("Yo'nalishlar") }}
                    </button>
                </template>
                <button
                    v-else
                    type="button"
                    :class="primaryButtonClass"
                    :disabled="restoring"
                    @click="restoreReviewer"
                >
                    <UserRoundPlus class="size-4" />
                    {{ t('Qayta taqrizchi qilish') }}
                </button>
                <Link
                    v-if="urls.author"
                    :href="urls.author"
                    :class="secondaryButtonClass"
                >
                    <PenTool class="size-4" /> {{ t('Muallif profili') }}
                </Link>
                <Link
                    v-if="urls.user"
                    :href="urls.user"
                    :class="secondaryButtonClass"
                >
                    <ShieldCheck class="size-4" /> {{ t('Hisob') }}
                </Link>
                <button
                    v-if="isReviewer"
                    type="button"
                    :class="dangerButton"
                    @click="removeOpen = true"
                >
                    <UserMinus class="size-4" /> {{ t('Chiqarish') }}
                </button>
            </template>
        </PersonHeader>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[22rem_minmax(0,1fr)]">
            <div class="flex min-w-0 flex-col gap-5">
                <ProfileFacts :profile="profile" :subjects="subjects" />
                <p
                    v-if="isReviewer && !subjects.length"
                    class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800"
                >
                    {{
                        t(
                            "Yo'nalishlar belgilanmagan — maqolaga taqrizchi tanlashda «Mos yo'nalish» belgisi chiqmaydi.",
                        )
                    }}
                    <button
                        type="button"
                        class="font-semibold underline hover:no-underline"
                        @click="openSubjects"
                    >
                        {{ t('Belgilash') }}
                    </button>
                </p>
            </div>

            <div class="flex min-w-0 flex-col gap-5">
                <section
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 2xl:grid-cols-4"
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

                <SectionCard
                    v-if="recommendationTotal"
                    :title="t('Tavsiyalar')"
                    :description="
                        t('Yakunlangan taqrizlardagi xulosalar taqsimoti')
                    "
                    :icon="ClipboardList"
                >
                    <div
                        class="flex h-2.5 overflow-hidden rounded-full bg-[#eef2f8]"
                    >
                        <template
                            v-for="(meta, key) in recommendationMeta"
                            :key="key"
                        >
                            <span
                                v-if="stats.recommendations[key]"
                                :class="
                                    cn(
                                        'h-full border-r-2 border-white last:border-r-0',
                                        meta.bar,
                                    )
                                "
                                :style="{
                                    width: `${(stats.recommendations[key] / recommendationTotal) * 100}%`,
                                }"
                            />
                        </template>
                    </div>
                    <ul
                        class="mt-3 grid grid-cols-1 gap-2 text-xs sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <li
                            v-for="(meta, key) in recommendationMeta"
                            :key="key"
                            class="flex items-center gap-2"
                        >
                            <span
                                :class="cn('size-2.5 rounded-full', meta.bar)"
                            />
                            <span class="text-navy-600">{{ meta.label }}</span>
                            <span
                                class="ml-auto font-semibold text-navy-900 tabular-nums"
                                >{{ stats.recommendations[key] ?? 0 }}</span
                            >
                        </li>
                    </ul>
                </SectionCard>

                <SectionCard
                    :title="t('Taqrizlar tarixi')"
                    :description="
                        t('Jami :invited ta taklif', { invited: stats.invited })
                    "
                    :icon="NotebookPen"
                >
                    <div class="mb-3 flex flex-wrap gap-1.5">
                        <button
                            v-for="item in filters"
                            :key="item.value"
                            type="button"
                            :class="
                                cn(
                                    'inline-flex h-7 items-center rounded-lg px-2.5 text-xs font-semibold transition-all',
                                    filter === item.value
                                        ? 'bg-navy-900 text-white'
                                        : 'bg-[#f1f4f9] text-navy-600 hover:bg-brand-50 hover:text-brand-700',
                                )
                            "
                            @click="filter = item.value"
                        >
                            {{ item.label }}
                        </button>
                    </div>

                    <ul v-if="visible.length" class="-mx-2 grid grid-cols-1">
                        <li v-for="review in visible" :key="review.id">
                            <component
                                :is="review.article.url ? Link : 'div'"
                                :href="review.article.url ?? undefined"
                                class="group flex flex-col gap-2 rounded-lg px-2 py-3 transition-colors hover:bg-brand-50/50 sm:flex-row sm:items-center"
                            >
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="flex flex-wrap items-center gap-x-2 text-[11px] text-navy-500"
                                    >
                                        <span
                                            class="font-mono font-semibold text-navy-600"
                                            >{{ review.article.code }}</span
                                        >
                                        <span
                                            >·
                                            {{
                                                t(':number-raund', {
                                                    number: review.round,
                                                })
                                            }}</span
                                        >
                                        <span v-if="review.article.subject"
                                            >·
                                            {{ review.article.subject }}</span
                                        >
                                    </p>
                                    <p
                                        class="mt-0.5 line-clamp-2 text-[13px] font-semibold [overflow-wrap:anywhere] text-navy-950 transition-colors group-hover:text-brand-700"
                                    >
                                        {{ review.article.title }}
                                    </p>
                                </div>
                                <div
                                    class="flex shrink-0 flex-wrap items-center gap-2 text-xs text-navy-500 sm:justify-end"
                                >
                                    <span
                                        v-if="review.recommendationLabel"
                                        class="font-medium text-navy-700"
                                        >{{ review.recommendationLabel }}</span
                                    >
                                    <span
                                        v-if="review.score !== null"
                                        class="inline-flex items-center gap-0.5 font-semibold text-gold-700 tabular-nums"
                                    >
                                        <Star class="size-3.5 fill-current" />
                                        {{ review.score.toFixed(1) }}
                                    </span>
                                    <span
                                        :class="
                                            cn(
                                                'tabular-nums',
                                                review.isOverdue &&
                                                    'font-semibold text-red-600',
                                            )
                                        "
                                        :title="
                                            review.completedAt
                                                ? t('Topshirilgan sana')
                                                : t('Muddat')
                                        "
                                    >
                                        {{
                                            review.completedAt
                                                ? formatDate(review.completedAt)
                                                : review.status ===
                                                        'declined' ||
                                                    review.status ===
                                                        'cancelled'
                                                  ? formatDate(review.invitedAt)
                                                  : review.dueAt
                                                    ? t('muddat: :date', {
                                                          date: formatDate(
                                                              review.dueAt,
                                                          ),
                                                      })
                                                    : formatDate(
                                                          review.invitedAt,
                                                      )
                                        }}
                                    </span>
                                    <span
                                        :class="
                                            cn(
                                                'rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset',
                                                review.isOverdue
                                                    ? 'bg-red-50 text-red-700 ring-red-200'
                                                    : statusTone[review.status],
                                            )
                                        "
                                        >{{
                                            review.isOverdue
                                                ? "Muddati o'tgan"
                                                : review.statusLabel
                                        }}</span
                                    >
                                </div>
                            </component>
                        </li>
                    </ul>
                    <p
                        v-else
                        class="rounded-lg border border-dashed border-line px-4 py-8 text-center text-sm text-navy-400"
                    >
                        {{ t("Taqrizlar yo'q") }}
                    </p>
                </SectionCard>
            </div>
        </div>

        <ActionDialog
            v-model:open="pauseOpen"
            :title="
                isPaused
                    ? t('Taqrizchini faollashtirish')
                    : t('Vaqtincha to\'xtatish')
            "
            :description="
                isPaused
                    ? t(
                          'Taqrizchi yana maqolaga taqrizchi tanlash ro\'yxatida chiqadi.',
                      )
                    : t(
                          'To\'xtatilgan taqrizchiga yangi taklif yuborilmaydi (ta\'til, bandlik). Joriy taqrizlari davom etadi.',
                      )
            "
            :icon="isPaused ? CirclePlay : CirclePause"
            :confirm-text="isPaused ? t('Faollashtirish') : t('To\'xtatish')"
            :processing="pausing"
            @confirm="togglePause"
        />

        <ActionDialog
            v-model:open="subjectsOpen"
            :title="t('Ilmiy yo\'nalishlar')"
            :description="
                t(
                    'Maqolaga taqrizchi tanlashda yo\'nalishi mos taqrizchilar ro\'yxat boshida «Mos yo\'nalish» belgisi bilan chiqadi.',
                )
            "
            :icon="FolderTree"
            :confirm-text="t('Saqlash')"
            :processing="subjectsForm.processing"
            size="lg"
            @confirm="saveSubjects"
        >
            <SubjectPicker
                v-model="subjectsForm.subject_ids"
                :options="subjectOptions"
            />
            <p
                v-if="subjectsForm.errors.subject_ids"
                class="mt-2 text-xs font-medium text-red-600"
            >
                {{ subjectsForm.errors.subject_ids }}
            </p>
        </ActionDialog>

        <DeleteDialog
            v-model:open="removeOpen"
            :url="urls.destroy"
            :title="t('Taqrizchilikdan chiqarish')"
            :description="
                t(
                    ':name dan «Taqrizchi» roli olinadi. Yakunlangan taqrizlar tarixi saqlanadi; faol taqrizlari bo\'lsa, avval ularni yakunlang yoki bekor qiling.',
                    { name: profile.name },
                )
            "
        />
    </div>
</template>
