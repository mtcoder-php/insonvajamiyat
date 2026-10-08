<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Eye, PenLine, UserRoundPlus } from '@lucide/vue';
import { computed, watch } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import FormField from '@/components/admin/ui/FormField.vue';
import { inputClass } from '@/lib/formStyles';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type {
    SettingsBoardMember,
    SettingsBoardRole,
    Translated,
} from '@/types';
import CheckCard from './CheckCard.vue';
import ImagePicker from './ImagePicker.vue';
import TranslatableField from './TranslatableField.vue';

/** Tahririyat kengashi a'zosi: rol, rasm, F.I.Sh., lavozim, tashkilot, ilmiy daraja, ORCID */
const props = defineProps<{
    member: SettingsBoardMember | null;
    storeUrl: string;
    roles: { value: SettingsBoardRole; label: string }[];
    defaultRole?: SettingsBoardRole;
}>();

const open = defineModel<boolean>('open', { default: false });

type Form = {
    role: SettingsBoardRole;
    full_name: Translated;
    position: Translated;
    organization: Translated;
    academic_degree: Translated;
    country: string;
    email: string;
    orcid: string;
    photo: File | null;
    remove_photo: boolean;
    is_active: boolean;
    sort_order: number;
};

const empty = (): Translated => ({ uz: '', ru: '', en: '' });
const blank = (): Form => ({
    role: props.defaultRole ?? 'member',
    full_name: empty(),
    position: empty(),
    organization: empty(),
    academic_degree: empty(),
    country: 'UZ',
    email: '',
    orcid: '',
    photo: null,
    remove_photo: false,
    is_active: true,
    sort_order: 0,
});

const form = useForm<Form>(blank());

watch(open, (value) => {
    if (!value) {
        return;
    }

    const m = props.member;
    form.defaults(
        m
            ? {
                  role: m.role,
                  full_name: { ...m.translations.full_name },
                  position: { ...m.translations.position },
                  organization: { ...m.translations.organization },
                  academic_degree: { ...m.translations.academic_degree },
                  country: m.country ?? '',
                  email: m.email ?? '',
                  orcid: m.orcid ?? '',
                  photo: null,
                  remove_photo: false,
                  is_active: m.isActive,
                  sort_order: m.sortOrder,
              }
            : blank(),
    );
    form.reset();
    form.clearErrors();
});

const errors = computed(() => form.errors as Record<string, string>);

function submit(): void {
    const options = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => (open.value = false),
    };

    if (props.member) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(
            props.member.urls.update,
            options,
        );
    } else {
        form.transform((data) => data).post(props.storeUrl, options);
    }
}
</script>

<template>
    <ActionDialog
        v-model:open="open"
        :title="member ? t('A\'zoni tahrirlash') : t('Yangi a\'zo')"
        :description="
            t(
                '«Jurnal haqida» sahifasidagi «Tahririyat kengashi» bo\'limida ko\'rinadi.',
            )
        "
        :icon="member ? PenLine : UserRoundPlus"
        :confirm-text="member ? t('Saqlash') : t('Qo\'shish')"
        :processing="form.processing"
        size="lg"
        @confirm="submit"
    >
        <div class="grid grid-cols-1 gap-4">
            <div
                class="grid grid-cols-2 gap-1 rounded-xl bg-[#eef3fa] p-1 sm:grid-cols-4"
                role="radiogroup"
                :aria-label="t('Lavozimi (kengashda)')"
            >
                <button
                    v-for="item in roles"
                    :key="item.value"
                    type="button"
                    role="radio"
                    :aria-checked="form.role === item.value"
                    :class="
                        cn(
                            'inline-flex min-h-9 items-center justify-center rounded-lg px-2 py-1.5 text-center text-[12px] leading-tight font-semibold transition-all',
                            form.role === item.value
                                ? 'bg-white text-brand-700 shadow-sm'
                                : 'text-navy-500 hover:text-navy-800',
                        )
                    "
                    @click="form.role = item.value"
                >
                    {{ item.label }}
                </button>
            </div>
            <p v-if="errors.role" class="-mt-2 text-xs text-red-600">
                {{ errors.role }}
            </p>

            <div
                class="grid grid-cols-1 gap-4 sm:grid-cols-[11rem_minmax(0,1fr)]"
            >
                <ImagePicker
                    v-model:file="form.photo"
                    v-model:removed="form.remove_photo"
                    :current-url="member?.photoUrl ?? null"
                    :error="errors.photo"
                    aspect-class="aspect-[4/5]"
                    :prepare="{ maxSide: 900, maxBytes: 1_500_000 }"
                    :hint="t('Portret · JPG/PNG/WEBP · 4 MB gacha')"
                />
                <div class="grid min-w-0 grid-cols-1 content-start gap-4">
                    <TranslatableField
                        v-model="form.full_name"
                        :label="t('F.I.Sh.')"
                        field="full_name"
                        :errors="errors"
                        required
                        :maxlength="150"
                        :placeholder="t('Karimov Akmal Anvarovich')"
                    />
                    <TranslatableField
                        v-model="form.academic_degree"
                        :label="t('Ilmiy daraja va unvon')"
                        field="academic_degree"
                        :errors="errors"
                        :maxlength="150"
                        :placeholder="t('Falsafa fanlari doktori, professor')"
                    />
                </div>
            </div>

            <TranslatableField
                v-model="form.position"
                :label="t('Lavozimi')"
                field="position"
                :errors="errors"
                :maxlength="200"
                :placeholder="t('Falsafa kafedrasi mudiri')"
            />
            <TranslatableField
                v-model="form.organization"
                :label="t('Tashkilot')"
                field="organization"
                :errors="errors"
                :maxlength="200"
                :placeholder="t('O\'zbekiston Milliy universiteti')"
            />

            <div
                class="grid grid-cols-1 gap-3 sm:grid-cols-[6rem_minmax(0,1fr)_minmax(0,1fr)_6rem]"
            >
                <FormField
                    :label="t('Davlat')"
                    for="board-country"
                    :error="errors.country"
                    hint="UZ, RU…"
                >
                    <input
                        id="board-country"
                        v-model.trim="form.country"
                        maxlength="2"
                        :class="cn(inputClass, 'uppercase')"
                        placeholder="UZ"
                    />
                </FormField>
                <FormField
                    :label="t('Elektron pochta')"
                    for="board-email"
                    :error="errors.email"
                    :hint="t('Saytda ko\'rsatilmaydi')"
                >
                    <input
                        id="board-email"
                        v-model.trim="form.email"
                        type="email"
                        maxlength="255"
                        :class="inputClass"
                        placeholder="email@example.com"
                    />
                </FormField>
                <FormField
                    label="ORCID"
                    for="board-orcid"
                    :error="errors.orcid"
                    hint="0000-0000-0000-0000"
                >
                    <input
                        id="board-orcid"
                        v-model.trim="form.orcid"
                        maxlength="19"
                        :class="cn(inputClass, 'font-mono tabular-nums')"
                        placeholder="0000-0002-1825-0097"
                    />
                </FormField>
                <FormField
                    :label="t('Tartib')"
                    for="board-order"
                    :error="errors.sort_order"
                >
                    <input
                        id="board-order"
                        v-model.number="form.sort_order"
                        type="number"
                        min="0"
                        :class="cn(inputClass, 'tabular-nums')"
                    />
                </FormField>
            </div>
            <CheckCard
                v-model="form.is_active"
                :label="t('Faol')"
                :hint="t('Belgilanmasa — saytda ko\'rinmaydi')"
                :icon="Eye"
            />
        </div>
    </ActionDialog>
</template>
