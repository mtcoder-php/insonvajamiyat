<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { CircleCheck, MailCheck, PenLine } from '@lucide/vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import { send } from '@/routes/verification';
import { t } from '@/lib/i18n';

/**
 * Elektron pochtani tasdiqlash. Havola yuborilgan manzil ko'rsatiladi;
 * manzil xato bo'lsa — profil sahifasida tuzatish mumkin (u yerga
 * tasdiqlanmagan foydalanuvchi ham kira oladi).
 */
defineOptions({
    layout: {
        title: 'Elektron pochtani tasdiqlang',
        description:
            "Pochtangizga yuborilgan havolani bosib, manzilingizni tasdiqlang. Xat kelmagan bo'lsa, 'Spam' papkasini ham tekshiring.",
    },
});

defineProps<{
    status?: string;
    email?: string | null;
}>();
</script>

<template>
    <Head :title="t('Pochtani tasdiqlash')" />

    <div
        v-if="email"
        class="mb-5 flex items-center gap-3 rounded-lg border border-line bg-[#f8fafc] px-4 py-3"
    >
        <span
            class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600"
        >
            <MailCheck class="size-4" />
        </span>
        <div class="min-w-0 flex-1">
            <p class="text-xs text-navy-500">
                {{ t('Havola yuborilgan manzil') }}
            </p>
            <p class="truncate text-sm font-semibold text-navy-900">
                {{ email }}
            </p>
        </div>
        <Link
            :href="editProfile()"
            class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-brand-700 hover:text-brand-600"
            :title="t('Manzil xato bo\'lsa, profilda o\'zgartiring')"
        >
            <PenLine class="size-3.5" />
            {{ t("O'zgartirish") }}
        </Link>
    </div>

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-6 flex items-start gap-2 rounded-lg border border-success/30 bg-success-soft px-4 py-3 text-sm font-medium text-success-ink"
    >
        <CircleCheck class="mt-0.5 size-4 shrink-0" />
        {{
            t(
                'Yangi tasdiqlash havolasi yuborildi. Pochtangizni (va "Spam" papkasini) tekshiring.',
            )
        }}
    </div>

    <Form
        v-bind="send.form()"
        class="space-y-6 text-center"
        v-slot="{ processing }"
    >
        <Button :disabled="processing" variant="secondary">
            <Spinner v-if="processing" />
            {{ t('Havolani qayta yuborish') }}
        </Button>

        <TextLink :href="logout()" as="button" class="mx-auto block text-sm">
            {{ t('Tizimdan chiqish') }}
        </TextLink>
    </Form>
</template>
