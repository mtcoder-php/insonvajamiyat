<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import {
    Check,
    LoaderCircle,
    Search,
    UserPlus,
    UserRoundPlus,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import UserAvatar from '@/components/users/UserAvatar.vue';
import { inputClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import type { PeopleOption, ReviewerCandidate } from '@/types';
import SubjectPicker from './SubjectPicker.vue';
import { t } from '@/lib/i18n';

/**
 * Taqrizchi qo'shish: mavjud foydalanuvchini (ism, email, tashkilot) qidirib topish,
 * yo'nalishlarini belgilash va "Taqrizchi" rolini berish.
 */
const props = defineProps<{
    storeUrl: string;
    candidatesUrl: string;
    createUserUrl: string | null;
    subjects: PeopleOption[];
}>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm<{ user_id: number | null; subject_ids: number[] }>({
    user_id: null,
    subject_ids: [],
});

const term = ref('');
const results = ref<ReviewerCandidate[]>([]);
const selected = ref<ReviewerCandidate | null>(null);
const loading = ref(false);
let timer: ReturnType<typeof setTimeout> | undefined;
let controller: AbortController | null = null;

watch(open, (value) => {
    if (value) {
        form.reset();
        form.clearErrors();
        term.value = '';
        results.value = [];
        selected.value = null;
    }
});

async function search(): Promise<void> {
    const q = term.value.trim();

    if (q.length < 2) {
        results.value = [];

        return;
    }

    controller?.abort();
    controller = new AbortController();
    loading.value = true;

    try {
        const url = `${props.candidatesUrl}?q=${encodeURIComponent(q)}`;
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            signal: controller.signal,
        });
        const json = (await response.json()) as { data: ReviewerCandidate[] };
        results.value = json.data;
    } catch {
        // bekor qilingan so'rov — e'tiborsiz
    } finally {
        loading.value = false;
    }
}

watch(term, () => {
    clearTimeout(timer);
    timer = setTimeout(search, 300);
});

function pick(candidate: ReviewerCandidate): void {
    selected.value = candidate;
    form.user_id = candidate.id;
}

const errors = computed(() => form.errors as Record<string, string>);

function submit(): void {
    form.post(props.storeUrl, {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <ActionDialog
        v-model:open="open"
        :title="t('Taqrizchi qo\'shish')"
        :description="
            t(
                'Ro\'yxatdan o\'tgan foydalanuvchiga «Taqrizchi» roli beriladi. U admin panelga kirib, taklif qilingan maqolalarni ko\'rib chiqa oladi.',
            )
        "
        :icon="UserRoundPlus"
        :confirm-text="t('Taqrizchi qilish')"
        :processing="form.processing"
        :disabled="!form.user_id"
        size="lg"
        @confirm="submit"
    >
        <div class="grid grid-cols-1 gap-4">
            <FormField
                :label="t('Foydalanuvchi')"
                for="reviewer-search"
                required
                :error="errors.user_id"
            >
                <label class="relative block">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                    />
                    <input
                        id="reviewer-search"
                        v-model="term"
                        type="search"
                        autocomplete="off"
                        :class="cn(inputClass, 'pl-9')"
                        :placeholder="
                            t('Ism, email yoki tashkilot (kamida 2 harf)...')
                        "
                    />
                    <LoaderCircle
                        v-if="loading"
                        class="absolute top-1/2 right-3 size-4 -translate-y-1/2 animate-spin text-navy-400"
                    />
                </label>
            </FormField>

            <ul
                v-if="results.length"
                class="-mx-1 grid max-h-60 grid-cols-1 gap-1 overflow-y-auto px-1"
                role="listbox"
            >
                <li v-for="candidate in results" :key="candidate.id">
                    <button
                        type="button"
                        role="option"
                        :aria-selected="form.user_id === candidate.id"
                        :class="
                            cn(
                                'flex w-full items-center gap-3 rounded-lg border px-3 py-2 text-left transition-all',
                                form.user_id === candidate.id
                                    ? 'border-brand-400 bg-brand-50'
                                    : 'border-line hover:-translate-y-px hover:border-brand-200 hover:shadow-sm',
                            )
                        "
                        @click="pick(candidate)"
                    >
                        <UserAvatar
                            :name="candidate.name"
                            :url="candidate.avatarUrl"
                            size="sm"
                        />
                        <span class="min-w-0 flex-1">
                            <span
                                class="block truncate text-[13px] font-semibold text-navy-900"
                                >{{ candidate.name }}</span
                            >
                            <span
                                class="block truncate text-[11px] text-navy-500"
                                >{{ candidate.email
                                }}<template v-if="candidate.organization">
                                    · {{ candidate.organization }}</template
                                ></span
                            >
                        </span>
                        <span
                            :class="
                                cn(
                                    'flex size-5 shrink-0 items-center justify-center rounded-full border',
                                    form.user_id === candidate.id
                                        ? 'border-brand-600 bg-brand-600 text-white'
                                        : 'border-navy-200',
                                )
                            "
                        >
                            <Check
                                v-if="form.user_id === candidate.id"
                                class="size-3"
                                :stroke-width="3"
                            />
                        </span>
                    </button>
                </li>
            </ul>
            <div
                v-else-if="term.trim().length >= 2 && !loading"
                class="rounded-lg border border-dashed border-line px-4 py-5 text-center text-xs text-navy-500"
            >
                {{
                    t(
                        'Mos foydalanuvchi topilmadi (taqrizchilar, bloklangan va emaili tasdiqlanmaganlar chiqmaydi).',
                    )
                }}
                <Link
                    v-if="createUserUrl"
                    :href="createUserUrl"
                    class="mt-2 flex items-center justify-center gap-1 font-semibold text-brand-700 hover:text-brand-600"
                >
                    <UserPlus class="size-3.5" />
                    {{ t('Yangi foydalanuvchi yaratish') }}
                </Link>
            </div>

            <div v-if="selected" class="grid gap-2">
                <p class="text-[13px] font-semibold text-navy-800">
                    {{ t("Ilmiy yo'nalishlari") }}
                    <span class="font-normal text-navy-400">{{
                        t(
                            "— taklif qilishda mos taqrizchilar birinchi ko'rsatiladi",
                        )
                    }}</span>
                </p>
                <SubjectPicker v-model="form.subject_ids" :options="subjects" />
                <p
                    v-if="errors.subject_ids"
                    class="text-xs font-medium text-red-600"
                >
                    {{ errors.subject_ids }}
                </p>
            </div>
        </div>
    </ActionDialog>
</template>
