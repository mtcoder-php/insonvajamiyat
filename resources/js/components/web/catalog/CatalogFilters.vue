<script setup lang="ts">
import {
    Check,
    ChevronDown,
    Filter,
    RotateCcw,
    Search,
    SlidersHorizontal,
} from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import { inputClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import type { CatalogFilters, CatalogProps } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Chap panel: yo'nalishlar (sonlar bilan), yil va son, muallif, kalit so'z.
 * "Filtrlarni qo'llash" — apply hodisasi bilan yangi filtrlar qaytariladi.
 */
const props = defineProps<{
    filters: CatalogFilters;
    facets: CatalogProps['facets'];
}>();

const emit = defineEmits<{
    apply: [filters: Partial<CatalogFilters>];
    reset: [];
}>();

const state = reactive({
    subjects: [...props.filters.subjects],
    year: props.filters.year,
    issue: props.filters.issue,
    author: props.filters.author ?? '',
    keyword: props.filters.keyword ?? '',
});

watch(
    () => props.filters,
    (f) => {
        state.subjects = [...f.subjects];
        state.year = f.year;
        state.issue = f.issue;
        state.author = f.author ?? '';
        state.keyword = f.keyword ?? '';
    },
);

const open = reactive({
    subjects: true,
    issue: true,
    author: true,
    keyword: true,
});
const showAllSubjects = ref(false);
const SUBJECTS_VISIBLE = 7;

const subjects = computed(() =>
    showAllSubjects.value
        ? props.facets.subjects
        : props.facets.subjects.slice(0, SUBJECTS_VISIBLE),
);

function toggleSubject(slug: string): void {
    state.subjects = state.subjects.includes(slug)
        ? state.subjects.filter((s) => s !== slug)
        : [...state.subjects, slug];
}

function apply(): void {
    emit('apply', {
        subjects: state.subjects,
        year: state.year,
        issue: state.issue,
        author: state.author.trim() || null,
        keyword: state.keyword.trim() || null,
    });
}

const groupHeader =
    'flex w-full items-center justify-between py-1 text-[13px] font-bold text-navy-900 transition-colors hover:text-brand-700';
</script>

<template>
    <form
        class="rounded-2xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        @submit.prevent="apply"
    >
        <header class="mb-4 flex items-center justify-between">
            <h2
                class="flex items-center gap-2 font-serif text-base font-bold text-navy-950"
            >
                <Filter class="size-4 text-brand-600" />
                {{ t('Qidiruv filtrlari') }}
            </h2>
            <button
                type="button"
                class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:underline"
                @click="emit('reset')"
            >
                <RotateCcw class="size-3.5" /> {{ t('Tozalash') }}
            </button>
        </header>

        <!-- Yo'nalishlar -->
        <section class="border-t border-line py-3">
            <button
                type="button"
                :class="groupHeader"
                :aria-expanded="open.subjects"
                @click="open.subjects = !open.subjects"
            >
                {{ t("Fan yo'nalishi") }}
                <ChevronDown
                    :class="
                        cn(
                            'size-4 transition-transform',
                            open.subjects && 'rotate-180',
                        )
                    "
                />
            </button>
            <ul v-show="open.subjects" class="mt-2 grid gap-1">
                <li v-for="subject in subjects" :key="subject.slug">
                    <button
                        type="button"
                        role="checkbox"
                        :aria-checked="state.subjects.includes(subject.slug)"
                        class="group flex w-full items-center gap-2.5 rounded-md px-1 py-1 text-left text-[13px] text-navy-700 transition-colors hover:bg-brand-50/60"
                        @click="toggleSubject(subject.slug)"
                    >
                        <span
                            :class="
                                cn(
                                    'flex size-4 shrink-0 items-center justify-center rounded border transition-colors',
                                    state.subjects.includes(subject.slug)
                                        ? 'border-brand-600 bg-brand-600 text-white'
                                        : 'border-navy-200 group-hover:border-brand-300',
                                )
                            "
                        >
                            <Check
                                v-if="state.subjects.includes(subject.slug)"
                                class="size-3"
                                :stroke-width="3"
                            />
                        </span>
                        <span class="flex-1">{{ subject.name }}</span>
                        <span class="text-[11px] text-navy-400 tabular-nums">{{
                            subject.count
                        }}</span>
                    </button>
                </li>
                <li v-if="facets.subjects.length > SUBJECTS_VISIBLE">
                    <button
                        type="button"
                        class="mt-1 text-xs font-semibold text-brand-700 hover:underline"
                        :aria-expanded="showAllSubjects"
                        @click="showAllSubjects = !showAllSubjects"
                    >
                        {{
                            showAllSubjects
                                ? t("Kamroq ko'rsatish")
                                : t("Barchasini ko'rish (:count)", {
                                      count: facets.subjects.length,
                                  })
                        }}
                    </button>
                </li>
            </ul>
        </section>

        <!-- Yil / son -->
        <section class="border-t border-line py-3">
            <button
                type="button"
                :class="groupHeader"
                :aria-expanded="open.issue"
                @click="open.issue = !open.issue"
            >
                {{ t('Jurnal soni / Yil') }}
                <ChevronDown
                    :class="
                        cn(
                            'size-4 transition-transform',
                            open.issue && 'rotate-180',
                        )
                    "
                />
            </button>
            <div v-show="open.issue" class="mt-2 grid gap-2">
                <SelectInput
                    v-model="state.year"
                    :aria-label="t('Yil')"
                    class="text-[13px]"
                >
                    <option :value="null">{{ t('Barcha yillar') }}</option>
                    <option v-for="y in facets.years" :key="y" :value="y">
                        {{ y }}
                    </option>
                </SelectInput>
                <SelectInput
                    v-model="state.issue"
                    :aria-label="t('Jurnal soni')"
                >
                    <option :value="null">{{ t('Barcha sonlar') }}</option>
                    <option
                        v-for="i in facets.issues"
                        :key="i.slug"
                        :value="i.slug"
                    >
                        {{ i.label }}
                    </option>
                </SelectInput>
            </div>
        </section>

        <!-- Muallif -->
        <section class="border-t border-line py-3">
            <button
                type="button"
                :class="groupHeader"
                :aria-expanded="open.author"
                @click="open.author = !open.author"
            >
                {{ t('Muallif') }}
                <ChevronDown
                    :class="
                        cn(
                            'size-4 transition-transform',
                            open.author && 'rotate-180',
                        )
                    "
                />
            </button>
            <label v-show="open.author" class="relative mt-2 block">
                <span class="sr-only">{{ t('Muallif') }}</span>
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                />
                <input
                    v-model="state.author"
                    type="search"
                    :placeholder="t('Muallif ismi bo\'yicha...')"
                    :class="cn(inputClass, 'h-9 pl-9 text-[13px]')"
                />
            </label>
        </section>

        <!-- Kalit so'z -->
        <section class="border-t border-line py-3">
            <button
                type="button"
                :class="groupHeader"
                :aria-expanded="open.keyword"
                @click="open.keyword = !open.keyword"
            >
                {{ t("Teglar / Kalit so'zlar") }}
                <ChevronDown
                    :class="
                        cn(
                            'size-4 transition-transform',
                            open.keyword && 'rotate-180',
                        )
                    "
                />
            </button>
            <input
                v-show="open.keyword"
                v-model="state.keyword"
                type="search"
                :placeholder="t('Kalit so\'zni kiriting...')"
                :aria-label="t('Kalit so\'z')"
                :class="cn(inputClass, 'mt-2 h-9 text-[13px]')"
            />
        </section>

        <button
            type="submit"
            class="mt-2 flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-navy-900 text-sm font-semibold text-white shadow-[0_10px_22px_-12px_rgba(0,30,60,0.9)] transition-all hover:-translate-y-px hover:bg-brand-700"
        >
            <SlidersHorizontal class="size-4" />
            {{ t("Filtrlarni qo'llash") }}
        </button>
    </form>
</template>
