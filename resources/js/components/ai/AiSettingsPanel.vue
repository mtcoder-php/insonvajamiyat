<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    KeyRound,
    LoaderCircle,
    Save,
    Search,
    ShieldCheck,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
import { inputClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import type { AiSettingsData } from '@/types';
import PromptEditor from './PromptEditor.vue';
import UsageLimitRow from './UsageLimitRow.vue';
import { t } from '@/lib/i18n';

/**
 * AI Studio → Sozlamalar (Super Admin): kalit va model, limitlar, ko'rsatmalar, foydalanuvchilar sarfi.
 * API kaliti hech qachon to'liq ko'rsatilmaydi — faqat yangisini kiritish mumkin.
 */
const props = defineProps<{ settings: AiSettingsData; indexUrl: string }>();

const form = useForm({
    enabled: props.settings.values.enabled,
    api_key: '',
    model: props.settings.values.model ?? '',
    author_monthly_limit: props.settings.values.authorLimit,
    staff_monthly_limit: props.settings.values.staffLimit,
    max_input_chars: props.settings.values.maxInputChars,
});

function save(): void {
    form.put(props.settings.urls.update, {
        preserveScroll: true,
        onSuccess: () => form.reset('api_key'),
    });
}

function forgetKey(): void {
    if (
        confirm(
            t(
                "Bazadagi API kaliti o'chirilsinmi? (.env dagi kalit bo'lsa, o'sha ishlatiladi)",
            ),
        )
    ) {
        router.delete(props.settings.urls.forgetKey, { preserveScroll: true });
    }
}

const search = ref(props.settings.search);

function find(): void {
    router.get(
        props.indexUrl,
        {
            tab: 'settings',
            ...(search.value.trim() ? { uq: search.value.trim() } : {}),
        },
        { preserveState: true, preserveScroll: true, only: ['settings'] },
    );
}

const keySourceLabel: Record<AiSettingsData['values']['keySource'], string> = {
    database: t('admin panelda kiritilgan (shifrlangan)'),
    env: t('.env faylidan'),
    none: t('kiritilmagan'),
};
</script>

<template>
    <div class="grid grid-cols-1 gap-5">
        <!-- Asosiy sozlamalar -->
        <form
            class="rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
            @submit.prevent="save"
        >
            <header
                class="mb-4 flex flex-wrap items-center justify-between gap-3"
            >
                <div>
                    <h2 class="font-sans text-[15px] font-bold text-navy-950">
                        {{ t('Anthropic Claude API') }}
                    </h2>
                    <p class="text-xs text-navy-500">
                        {{ t('Kalit, model va oylik limitlar') }}
                    </p>
                </div>
                <label
                    :class="
                        cn(
                            'inline-flex cursor-pointer items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold ring-1 transition-colors',
                            form.enabled
                                ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                : 'bg-navy-50 text-navy-500 ring-line',
                        )
                    "
                >
                    <input
                        v-model="form.enabled"
                        type="checkbox"
                        class="size-4 accent-emerald-600"
                    />
                    {{
                        form.enabled
                            ? t('Xizmat yoqilgan')
                            : "Xizmat o'chirilgan"
                    }}
                </label>
            </header>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="md:col-span-2">
                    <span
                        class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-navy-600"
                    >
                        <KeyRound class="size-3.5" /> {{ t('API kaliti') }}
                    </span>
                    <div class="flex gap-2">
                        <input
                            v-model="form.api_key"
                            type="password"
                            autocomplete="new-password"
                            :class="inputClass"
                            :placeholder="
                                settings.values.maskedKey
                                    ? t(
                                          'Joriy: :maskedKey — almashtirish uchun yangisini kiriting',
                                          {
                                              maskedKey:
                                                  settings.values.maskedKey,
                                          },
                                      )
                                    : 'sk-ant-…'
                            "
                            :aria-invalid="!!form.errors.api_key"
                        />
                        <button
                            v-if="settings.values.keySource === 'database'"
                            type="button"
                            class="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-lg border border-line px-3 text-xs font-semibold text-navy-600 transition-colors hover:border-red-200 hover:text-red-600"
                            @click="forgetKey"
                        >
                            <Trash2 class="size-4" /> {{ t("O'chirish") }}
                        </button>
                    </div>
                    <span
                        v-if="form.errors.api_key"
                        class="mt-1 block text-xs text-red-600"
                        >{{ form.errors.api_key }}</span
                    >
                    <span
                        class="mt-1 flex items-center gap-1 text-[11px] text-navy-400"
                    >
                        <ShieldCheck class="size-3.5 text-emerald-600" />
                        {{
                            t(
                                'Joriy kalit: :source. Kalit bazada shifrlangan holda saqlanadi.',
                                {
                                    source: keySourceLabel[
                                        settings.values.keySource
                                    ],
                                },
                            )
                        }}
                    </span>
                </label>

                <label>
                    <span
                        class="mb-1.5 block text-xs font-semibold text-navy-600"
                        >{{ t('Model identifikatori') }}</span
                    >
                    <input
                        v-model="form.model"
                        :class="inputClass"
                        :placeholder="t('Anthropic konsolidagi model nomi')"
                        :aria-invalid="!!form.errors.model"
                    />
                    <span
                        v-if="form.errors.model"
                        class="mt-1 block text-xs text-red-600"
                        >{{ form.errors.model }}</span
                    >
                </label>
                <label>
                    <span
                        class="mb-1.5 block text-xs font-semibold text-navy-600"
                        >{{ t("Bitta so'rovdagi matn (belgi)") }}</span
                    >
                    <input
                        v-model.number="form.max_input_chars"
                        type="number"
                        min="1000"
                        step="1000"
                        :class="inputClass"
                    />
                    <span
                        v-if="form.errors.max_input_chars"
                        class="mt-1 block text-xs text-red-600"
                        >{{ form.errors.max_input_chars }}</span
                    >
                </label>
                <label>
                    <span
                        class="mb-1.5 block text-xs font-semibold text-navy-600"
                        >{{ t('Muallif uchun oylik limit (token)') }}</span
                    >
                    <input
                        v-model.number="form.author_monthly_limit"
                        type="number"
                        min="0"
                        step="1000"
                        :class="inputClass"
                    />
                    <span class="mt-1 block text-[11px] text-navy-400">{{
                        t('0 — cheklanmagan')
                    }}</span>
                </label>
                <label>
                    <span
                        class="mb-1.5 block text-xs font-semibold text-navy-600"
                        >{{ t('Xodim uchun oylik limit (token)') }}</span
                    >
                    <input
                        v-model.number="form.staff_monthly_limit"
                        type="number"
                        min="0"
                        step="1000"
                        :class="inputClass"
                    />
                    <span class="mt-1 block text-[11px] text-navy-400">{{
                        t('0 — cheklanmagan')
                    }}</span>
                </label>
            </div>

            <div class="mt-5 flex justify-end">
                <button
                    type="submit"
                    :disabled="form.processing || !form.isDirty"
                    class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-600 px-5 text-sm font-semibold text-white shadow-[0_8px_20px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500 disabled:pointer-events-none disabled:opacity-60"
                >
                    <LoaderCircle
                        v-if="form.processing"
                        class="size-4 animate-spin"
                    />
                    <Save v-else class="size-4" /> {{ t('Saqlash') }}
                </button>
            </div>
        </form>

        <!-- Ko'rsatmalar -->
        <section>
            <h2 class="mb-1 font-sans text-[15px] font-bold text-navy-950">
                {{ t("Ko'rsatma shablonlari (prompt)") }}
            </h2>
            <p class="mb-3 text-xs text-navy-500">
                {{
                    t(
                        "AI ning ishlash qoidalari. Kodni o'zgartirmasdan tahrirlanadi; o'zgarish keyingi so'rovlardan kuchga kiradi.",
                    )
                }}
            </p>
            <div class="grid grid-cols-1 gap-3">
                <PromptEditor
                    v-for="prompt in settings.prompts"
                    :key="prompt.key"
                    :prompt="prompt"
                />
            </div>
        </section>

        <!-- Sarf va shaxsiy limitlar -->
        <section
            class="rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <header
                class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3"
            >
                <div>
                    <h2 class="font-sans text-[15px] font-bold text-navy-950">
                        {{ t('Tokenlar va balans') }}
                    </h2>
                    <p class="text-xs text-navy-500">
                        {{
                            t(
                                "Joriy oy sarfi va shaxsiy limitlar (bo'sh — rol bo'yicha standart)",
                            )
                        }}
                    </p>
                </div>
                <form class="relative" @submit.prevent="find">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                    />
                    <input
                        v-model="search"
                        :class="cn(inputClass, 'h-9 w-64 pl-9')"
                        :placeholder="t('Ism yoki email bo\'yicha qidirish')"
                    />
                </form>
            </header>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[40rem] text-left text-[13px]">
                    <thead
                        class="bg-[#fafcff] text-[11px] font-semibold tracking-wide text-navy-500 uppercase"
                    >
                        <tr>
                            <th class="px-4 py-2.5">
                                {{ t('Foydalanuvchi') }}
                            </th>
                            <th class="px-4 py-2.5 text-right">
                                {{ t("So'rovlar") }}
                            </th>
                            <th class="px-4 py-2.5">{{ t('Sarf / limit') }}</th>
                            <th class="px-4 py-2.5 text-right">
                                {{ t('Shaxsiy limit') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <UsageLimitRow
                            v-for="row in settings.usage"
                            :key="row.id"
                            :row="row"
                        />
                        <tr v-if="!settings.usage.length">
                            <td
                                colspan="4"
                                class="px-4 py-8 text-center text-sm text-navy-400"
                            >
                                {{
                                    settings.search
                                        ? t('Foydalanuvchi topilmadi')
                                        : t('Bu oy hali AI dan foydalanilmagan')
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
