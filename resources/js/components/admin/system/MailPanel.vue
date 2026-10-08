<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    KeyRound,
    LoaderCircle,
    Mail,
    MailCheck,
    Save,
    Send,
    Server,
    TriangleAlert,
} from '@lucide/vue';
import { computed } from 'vue';
import FormField from '@/components/admin/ui/FormField.vue';
import SectionCard from '@/components/admin/ui/SectionCard.vue';
import SelectInput from '@/components/admin/ui/SelectInput.vue';
import {
    inputClass,
    primaryButtonClass,
    secondaryButtonClass,
} from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import type { MailSettings, SystemPageProps } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Pochta (SMTP): server, port, shifrlash, login/parol, yuboruvchi. Parol frontendga
 * qaytarilmaydi — bo'sh qoldirilsa o'zgarmaydi. Test xat saqlangan sozlamalar bilan yuboriladi.
 */
const props = defineProps<{
    mail: MailSettings;
    password: SystemPageProps['mailPassword'];
    url: string;
    testUrl: string;
}>();

const form = useForm({
    mailer: props.mail.mailer,
    host: props.mail.host ?? '',
    port: props.mail.port === null ? '' : String(props.mail.port),
    scheme: props.mail.scheme,
    username: props.mail.username ?? '',
    password: '',
    from_address: props.mail.from_address ?? '',
    from_name: props.mail.from_name ?? '',
});

const test = useForm({ test_email: '' });

const errors = computed(() => form.errors as Record<string, string>);

const presets = [
    { label: 'Gmail', host: 'smtp.gmail.com', port: '587', scheme: 'smtp' },
    { label: 'Yandex', host: 'smtp.yandex.com', port: '465', scheme: 'smtps' },
    { label: 'Mail.ru', host: 'smtp.mail.ru', port: '465', scheme: 'smtps' },
    {
        label: 'Outlook',
        host: 'smtp.office365.com',
        port: '587',
        scheme: 'smtp',
    },
] as const;

function preset(item: (typeof presets)[number]): void {
    form.mailer = 'smtp';
    form.host = item.host;
    form.port = item.port;
    form.scheme = item.scheme;
}

function submit(): void {
    form.put(props.url, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.password = '';
            form.defaults();
        },
    });
}

function sendTest(): void {
    test.post(props.testUrl, { preserveScroll: true, preserveState: true });
}

const isSmtp = computed(() => form.mailer === 'smtp');
</script>

<template>
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <form class="grid min-w-0 grid-cols-1 gap-5" @submit.prevent="submit">
            <SectionCard
                :title="t('Pochta serveri (SMTP)')"
                :description="
                    t(
                        'Ro\'yxatdan o\'tish, parolni tiklash, maqola holati va boshqa xatlar shu orqali yuboriladi',
                    )
                "
                :icon="Server"
            >
                <div class="grid grid-cols-1 gap-4">
                    <div
                        class="grid grid-cols-2 gap-1 rounded-xl bg-[#eef3fa] p-1"
                        role="radiogroup"
                        :aria-label="t('Yuborish usuli')"
                    >
                        <button
                            v-for="item in [
                                {
                                    value: 'smtp',
                                    label: t('SMTP orqali yuborish'),
                                },
                                {
                                    value: 'log',
                                    label: t('Faqat logga yozish'),
                                },
                            ] as const"
                            :key="item.value"
                            type="button"
                            role="radio"
                            :aria-checked="form.mailer === item.value"
                            :class="
                                cn(
                                    'h-9 rounded-lg text-[13px] font-semibold transition-all',
                                    form.mailer === item.value
                                        ? 'bg-white text-brand-700 shadow-sm'
                                        : 'text-navy-500 hover:text-navy-800',
                                )
                            "
                            @click="form.mailer = item.value"
                        >
                            {{ item.label }}
                        </button>
                    </div>

                    <p
                        v-if="!isSmtp"
                        class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"
                    >
                        <TriangleAlert class="mt-0.5 size-4 shrink-0" />
                        {{
                            t(
                                'Xatlar foydalanuvchilarga yuborilmaydi — storage/logs/laravel.log fayliga yoziladi (faqat sinov uchun).',
                            )
                        }}
                    </p>

                    <template v-if="isSmtp">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-xs text-navy-500">{{
                                t('Tayyor sozlama:')
                            }}</span>
                            <button
                                v-for="item in presets"
                                :key="item.label"
                                type="button"
                                :class="
                                    cn(
                                        'inline-flex h-7 items-center rounded-lg border px-2.5 text-xs font-semibold transition-all hover:-translate-y-px',
                                        form.host === item.host
                                            ? 'border-brand-400 bg-brand-50 text-brand-700'
                                            : 'border-line bg-white text-navy-600 hover:border-brand-200 hover:text-brand-700',
                                    )
                                "
                                @click="preset(item)"
                            >
                                {{ item.label }}
                            </button>
                        </div>

                        <div
                            class="grid grid-cols-1 gap-4 sm:grid-cols-[minmax(0,1fr)_7rem_11rem]"
                        >
                            <FormField
                                :label="t('Server')"
                                for="m-host"
                                required
                                :error="errors.host"
                            >
                                <input
                                    id="m-host"
                                    v-model.trim="form.host"
                                    :class="cn(inputClass, 'font-mono')"
                                    placeholder="smtp.example.uz"
                                />
                            </FormField>
                            <FormField
                                :label="t('Port')"
                                for="m-port"
                                required
                                :error="errors.port"
                            >
                                <input
                                    id="m-port"
                                    v-model.trim="form.port"
                                    inputmode="numeric"
                                    :class="cn(inputClass, 'tabular-nums')"
                                    placeholder="587"
                                />
                            </FormField>
                            <FormField
                                :label="t('Shifrlash')"
                                for="m-scheme"
                                :error="errors.scheme"
                            >
                                <SelectInput
                                    id="m-scheme"
                                    v-model="form.scheme"
                                >
                                    <option value="smtp">STARTTLS (587)</option>
                                    <option value="smtps">SSL/TLS (465)</option>
                                </SelectInput>
                            </FormField>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <FormField
                                :label="t('Login')"
                                for="m-username"
                                :error="errors.username"
                            >
                                <input
                                    id="m-username"
                                    v-model.trim="form.username"
                                    autocomplete="off"
                                    :class="inputClass"
                                    placeholder="noreply@insonvajamiyat.uz"
                                />
                            </FormField>
                            <FormField
                                :label="t('Parol')"
                                for="m-password"
                                :error="errors.password"
                                :hint="
                                    password.set
                                        ? t(
                                              'Saqlangan (:source). Bo\'sh qoldirsangiz o\'zgarmaydi.',
                                              {
                                                  source:
                                                      password.source === 'env'
                                                          ? '.env'
                                                          : t(
                                                                'baza, shifrlangan',
                                                            ),
                                              },
                                          )
                                        : t(
                                              'Gmail uchun — «App password» (ilova paroli)',
                                          )
                                "
                            >
                                <div class="relative">
                                    <KeyRound
                                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                                    />
                                    <input
                                        id="m-password"
                                        v-model="form.password"
                                        type="password"
                                        autocomplete="new-password"
                                        :class="cn(inputClass, 'pl-9')"
                                        :placeholder="
                                            password.set
                                                ? t(
                                                      '•••••••• (o\'zgartirish uchun yangisini kiriting)',
                                                  )
                                                : ''
                                        "
                                    />
                                </div>
                            </FormField>
                        </div>
                    </template>
                </div>
            </SectionCard>

            <SectionCard
                :title="t('Yuboruvchi')"
                :description="
                    t(
                        'Xat «Kimdan» maydonida ko\'rinadi. Ko\'p serverlar login bilan bir xil manzilni talab qiladi.',
                    )
                "
                :icon="Mail"
            >
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField
                        :label="t('Email')"
                        for="m-from"
                        required
                        :error="errors.from_address"
                    >
                        <input
                            id="m-from"
                            v-model.trim="form.from_address"
                            type="email"
                            :class="inputClass"
                            placeholder="noreply@insonvajamiyat.uz"
                        />
                    </FormField>
                    <FormField
                        :label="t('Nomi')"
                        for="m-from-name"
                        required
                        :error="errors.from_name"
                    >
                        <input
                            id="m-from-name"
                            v-model="form.from_name"
                            :class="inputClass"
                            placeholder="Inson va Jamiyat"
                        />
                    </FormField>
                </div>
            </SectionCard>

            <div
                class="sticky bottom-3 z-10 flex flex-wrap items-center justify-end gap-3 rounded-xl border border-line bg-white/95 px-4 py-3 shadow-[0_12px_30px_-18px_rgba(0,36,66,0.45)] backdrop-blur"
            >
                <p v-if="form.isDirty" class="mr-auto text-xs text-gold-700">
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

        <aside class="min-w-0">
            <SectionCard
                :title="t('Test xat')"
                :description="t('Saqlangan sozlamalar bilan darhol yuboriladi')"
                :icon="MailCheck"
            >
                <form class="grid gap-3" @submit.prevent="sendTest">
                    <FormField
                        :label="t('Qabul qiluvchi')"
                        for="m-test"
                        :error="test.errors.test_email"
                    >
                        <input
                            id="m-test"
                            v-model.trim="test.test_email"
                            type="email"
                            :class="inputClass"
                            placeholder="siz@example.uz"
                        />
                    </FormField>
                    <p v-if="form.isDirty" class="text-xs text-amber-700">
                        {{
                            t(
                                "Avval o'zgarishlarni saqlang — test eski sozlamalar bilan yuboriladi.",
                            )
                        }}
                    </p>
                    <button
                        type="submit"
                        :disabled="test.processing || !test.test_email"
                        :class="cn(secondaryButtonClass, 'w-full')"
                    >
                        <LoaderCircle
                            v-if="test.processing"
                            class="size-4 animate-spin"
                        />
                        <Send v-else class="size-4" />
                        {{ t('Test xat yuborish') }}
                    </button>
                </form>
            </SectionCard>
        </aside>
    </div>
</template>
