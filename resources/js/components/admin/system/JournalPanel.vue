<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    AtSign,
    Building2,
    Landmark,
    LoaderCircle,
    MapPin,
    Phone,
    Save,
    Send,
} from '@lucide/vue';
import { computed } from 'vue';
import FormField from '@/components/admin/ui/FormField.vue';
import SectionCard from '@/components/admin/ui/SectionCard.vue';
import SocialIcon from '@/components/web/SocialIcon.vue';
import {
    inputClass,
    primaryButtonClass,
    textareaClass,
} from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import type { JournalSettings, JournalSocialNetwork } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Jurnal rekvizitlari (settings → "journal" guruhi): nom, ISSN, DOI, aloqa, ijtimoiy tarmoqlar,
 * bank rekvizitlari. Uchala tab bitta forma — saqlash hammasini birga yozadi.
 */
const props = defineProps<{
    journal: JournalSettings;
    section: 'journal' | 'contacts' | 'payment';
    url: string;
}>();

const form = useForm<Record<keyof JournalSettings, string>>(
    Object.fromEntries(
        Object.entries(props.journal).map(([k, v]) => [
            k,
            v === null || v === undefined ? '' : String(v),
        ]),
    ) as Record<keyof JournalSettings, string>,
);

const errors = computed(() => form.errors as Record<string, string>);

function submit(): void {
    form.put(props.url, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => form.defaults(),
    });
}

const socials: {
    key: keyof JournalSettings;
    label: string;
    network: JournalSocialNetwork;
    placeholder: string;
}[] = [
    {
        key: 'social_telegram',
        label: 'Telegram',
        network: 'telegram',
        placeholder: 'https://t.me/insonvajamiyat',
    },
    {
        key: 'social_facebook',
        label: 'Facebook',
        network: 'facebook',
        placeholder: 'https://facebook.com/…',
    },
    {
        key: 'social_instagram',
        label: 'Instagram',
        network: 'instagram',
        placeholder: 'https://instagram.com/…',
    },
    {
        key: 'social_youtube',
        label: 'YouTube',
        network: 'youtube',
        placeholder: 'https://youtube.com/@…',
    },
    {
        key: 'social_linkedin',
        label: 'LinkedIn',
        network: 'linkedin',
        placeholder: 'https://linkedin.com/company/…',
    },
];

// Hisob raqami: 20 raqamni 4 talik guruhlarda ko'rsatish (saqlashda bo'shliqlar qoladi — server qabul qiladi)
const accountPreview = computed(() =>
    form.payment_account.replace(/\s+/g, '').replace(/(\d{4})(?=\d)/g, '$1 '),
);
</script>

<template>
    <form class="grid grid-cols-1 gap-5" @submit.prevent="submit">
        <!-- Jurnal -->
        <SectionCard
            v-show="section === 'journal'"
            :title="t('Jurnal rekvizitlari')"
            :description="
                t(
                    'Sayt sarlavhasi, footer, «Jurnal haqida», maqola sahifalari va xatlarda ishlatiladi',
                )
            "
            :icon="Building2"
        >
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <FormField
                    :label="t('Jurnal nomi')"
                    for="j-name"
                    required
                    :error="errors.name"
                >
                    <input
                        id="j-name"
                        v-model="form.name"
                        maxlength="120"
                        :class="inputClass"
                    />
                </FormField>
                <FormField
                    :label="t('Qo\'shimcha nom')"
                    for="j-subtitle"
                    :error="errors.subtitle"
                    :hint="
                        t('Logotip ostidagi yozuv, masalan: Scientific Journal')
                    "
                >
                    <input
                        id="j-subtitle"
                        v-model="form.subtitle"
                        maxlength="120"
                        :class="inputClass"
                    />
                </FormField>
                <FormField
                    :label="t('Qisqa tavsif')"
                    for="j-description"
                    :error="errors.description"
                    class="md:col-span-2"
                    :hint="
                        t(
                            'SEO va «Jurnal haqida» kartasi uchun (500 belgigacha)',
                        )
                    "
                >
                    <textarea
                        id="j-description"
                        v-model="form.description"
                        rows="3"
                        maxlength="500"
                        :class="textareaClass"
                    />
                </FormField>
                <FormField
                    :label="t('ISSN (bosma)')"
                    for="j-issn"
                    :error="errors.issn"
                >
                    <input
                        id="j-issn"
                        v-model.trim="form.issn"
                        placeholder="1234-5678"
                        maxlength="9"
                        :class="cn(inputClass, 'font-mono tabular-nums')"
                    />
                </FormField>
                <FormField
                    :label="t('e-ISSN (elektron)')"
                    for="j-eissn"
                    :error="errors.eissn"
                >
                    <input
                        id="j-eissn"
                        v-model.trim="form.eissn"
                        placeholder="1234-567X"
                        maxlength="9"
                        :class="cn(inputClass, 'font-mono tabular-nums')"
                    />
                </FormField>
                <FormField
                    :label="t('DOI prefiksi')"
                    for="j-doi"
                    :error="errors.doi_prefix"
                    :hint="t('Masalan: 10.5281/zenodo yoki 10.12345')"
                >
                    <input
                        id="j-doi"
                        v-model.trim="form.doi_prefix"
                        placeholder="10.xxxxx"
                        :class="cn(inputClass, 'font-mono')"
                    />
                </FormField>
                <FormField
                    :label="t('Davriylik')"
                    for="j-frequency"
                    :error="errors.frequency"
                >
                    <input
                        id="j-frequency"
                        v-model="form.frequency"
                        :placeholder="t('Yiliga 4 marta (kvartal)')"
                        :class="inputClass"
                    />
                </FormField>
                <FormField
                    :label="t('Plagiat chegarasi (%)')"
                    for="j-plagiarism"
                    :error="errors.plagiarism_max"
                    :hint="
                        t(
                            'Nashr oldidan tekshiruvda o\'xshashlik shundan oshmasligi kerak',
                        )
                    "
                >
                    <input
                        id="j-plagiarism"
                        v-model="form.plagiarism_max"
                        type="number"
                        min="0"
                        max="100"
                        step="0.5"
                        :class="cn(inputClass, 'w-32 tabular-nums')"
                    />
                </FormField>
            </div>
        </SectionCard>

        <!-- Aloqa va tarmoqlar -->
        <div v-show="section === 'contacts'" class="grid grid-cols-1 gap-5">
            <SectionCard
                :title="t('Aloqa ma\'lumotlari')"
                :description="
                    t('Footer, «Aloqa» kartasi va xatlar pastida ko\'rsatiladi')
                "
                :icon="AtSign"
            >
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <FormField
                        :label="t('Email')"
                        for="j-email"
                        :error="errors.contact_email"
                    >
                        <div class="relative">
                            <AtSign
                                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                            />
                            <input
                                id="j-email"
                                v-model.trim="form.contact_email"
                                type="email"
                                :class="cn(inputClass, 'pl-9')"
                                placeholder="info@insonvajamiyat.uz"
                            />
                        </div>
                    </FormField>
                    <FormField
                        :label="t('Telefon')"
                        for="j-phone"
                        :error="errors.contact_phone"
                    >
                        <div class="relative">
                            <Phone
                                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                            />
                            <input
                                id="j-phone"
                                v-model.trim="form.contact_phone"
                                :class="cn(inputClass, 'pl-9 tabular-nums')"
                                placeholder="+998 71 234 56 78"
                            />
                        </div>
                    </FormField>
                    <FormField
                        :label="t('Manzil')"
                        for="j-address"
                        :error="errors.contact_address"
                        class="md:col-span-2"
                    >
                        <div class="relative">
                            <MapPin
                                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                            />
                            <input
                                id="j-address"
                                v-model="form.contact_address"
                                :class="cn(inputClass, 'pl-9')"
                                :placeholder="t('Toshkent sh., …')"
                            />
                        </div>
                    </FormField>
                </div>
            </SectionCard>

            <SectionCard
                :title="t('Ijtimoiy tarmoqlar')"
                :description="
                    t('Bo\'sh qoldirilgan tarmoqlar footer\'da ko\'rsatilmaydi')
                "
                :icon="Send"
            >
                <div class="grid grid-cols-1 gap-3">
                    <label
                        v-for="item in socials"
                        :key="item.key"
                        class="grid grid-cols-1 items-center gap-1.5 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-3"
                    >
                        <span
                            class="inline-flex items-center gap-2 text-[13px] font-semibold text-navy-800"
                        >
                            <span
                                :class="
                                    cn(
                                        'flex size-8 items-center justify-center rounded-lg transition-colors',
                                        form[item.key]
                                            ? 'bg-brand-600 text-white'
                                            : 'bg-[#eef3fa] text-navy-400',
                                    )
                                "
                            >
                                <SocialIcon :network="item.network" />
                            </span>
                            {{ item.label }}
                        </span>
                        <span>
                            <input
                                v-model.trim="form[item.key]"
                                type="url"
                                :placeholder="item.placeholder"
                                :class="inputClass"
                                :aria-invalid="!!errors[item.key]"
                            />
                            <span
                                v-if="errors[item.key]"
                                class="mt-1 block text-xs text-red-600"
                                >{{ errors[item.key] }}</span
                            >
                        </span>
                    </label>
                </div>
            </SectionCard>
        </div>

        <!-- To'lov rekvizitlari -->
        <SectionCard
            v-show="section === 'payment'"
            :title="t('Bank rekvizitlari')"
            :description="
                t(
                    'Muallif kabinetida «To\'lov kutilmoqda» holatida ko\'rsatiladi (bank orqali to\'lov, admin qo\'lda tasdiqlaydi). Bo\'sh maydonlar ko\'rsatilmaydi.',
                )
            "
            :icon="Landmark"
        >
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <FormField
                    :label="t('Qabul qiluvchi tashkilot')"
                    for="j-recipient"
                    :error="errors.payment_recipient"
                    class="md:col-span-2"
                >
                    <input
                        id="j-recipient"
                        v-model="form.payment_recipient"
                        :class="inputClass"
                        :placeholder="t('«Inson va Jamiyat» MChJ')"
                    />
                </FormField>
                <FormField
                    :label="t('Bank')"
                    for="j-bank"
                    :error="errors.payment_bank"
                    class="md:col-span-2"
                >
                    <input
                        id="j-bank"
                        v-model="form.payment_bank"
                        :class="inputClass"
                        :placeholder="t('ATB «…» Toshkent filiali')"
                    />
                </FormField>
                <FormField
                    :label="t('Hisob raqami')"
                    for="j-account"
                    :error="errors.payment_account"
                    class="md:col-span-2"
                    :hint="
                        form.payment_account ? accountPreview : t('20 ta raqam')
                    "
                >
                    <input
                        id="j-account"
                        v-model.trim="form.payment_account"
                        inputmode="numeric"
                        :class="cn(inputClass, 'font-mono tabular-nums')"
                        placeholder="2020 8000 0000 0000 0001"
                    />
                </FormField>
                <FormField label="MFO" for="j-mfo" :error="errors.payment_mfo">
                    <input
                        id="j-mfo"
                        v-model.trim="form.payment_mfo"
                        inputmode="numeric"
                        maxlength="5"
                        :class="cn(inputClass, 'font-mono tabular-nums')"
                        placeholder="00000"
                    />
                </FormField>
                <FormField
                    label="STIR (INN)"
                    for="j-inn"
                    :error="errors.payment_inn"
                >
                    <input
                        id="j-inn"
                        v-model.trim="form.payment_inn"
                        inputmode="numeric"
                        maxlength="9"
                        :class="cn(inputClass, 'font-mono tabular-nums')"
                        placeholder="000000000"
                    />
                </FormField>
            </div>
        </SectionCard>

        <div
            class="sticky bottom-3 z-10 flex flex-wrap items-center justify-end gap-3 rounded-xl border border-line bg-white/95 px-4 py-3 shadow-[0_12px_30px_-18px_rgba(0,36,66,0.45)] backdrop-blur"
        >
            <p v-if="form.hasErrors" class="mr-auto text-xs text-red-600">
                {{
                    t(
                        "Xatolarni tuzating (boshqa tablarda ham bo'lishi mumkin).",
                    )
                }}
            </p>
            <p v-else-if="form.isDirty" class="mr-auto text-xs text-gold-700">
                {{ t("Saqlanmagan o'zgarishlar bor") }}
            </p>
            <button
                type="submit"
                :disabled="form.processing || !form.isDirty"
                :class="primaryButtonClass"
            >
                <LoaderCircle
                    v-if="form.processing"
                    class="size-4 animate-spin"
                />
                <Save v-else class="size-4" />
                {{ t('Saqlash') }}
            </button>
        </div>
    </form>
</template>
