<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CircleCheck, Link2, Unlink } from '@lucide/vue';
import { ref } from 'vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import SectionCard from '@/components/admin/ui/SectionCard.vue';
import SocialProviderIcon from '@/components/auth/SocialProviderIcon.vue';
import { formatDate } from '@/lib/format';
import { primaryButtonClass, secondaryButtonClass } from '@/lib/formStyles';
import { t } from '@/lib/i18n';
import { destroy, redirect } from '@/routes/social';
import type { SocialAccountItem } from '@/types';

/**
 * Sozlamalar → Xavfsizlik: Google / ORCID akkauntlarini bog'lash va uzish.
 * Bog'lash — provayder sahifasi orqali (SocialAuthController, kirgan holda),
 * uzish — SocialAccountController::destroy. Parolsiz foydalanuvchi yagona usulni uza olmaydi.
 */
const props = defineProps<{
    accounts: SocialAccountItem[];
    hasPassword: boolean;
}>();

const removing = ref<SocialAccountItem | null>(null);
const removeOpen = ref(false);
const processing = ref(false);

const linkedCount = () => props.accounts.filter((a) => a.linked).length;

/** Parolsiz va yagona bog'langan akkaunt — uzib bo'lmaydi */
function isOnlyMethod(account: SocialAccountItem): boolean {
    return account.linked && !props.hasPassword && linkedCount() <= 1;
}

function askRemove(account: SocialAccountItem): void {
    removing.value = account;
    removeOpen.value = true;
}

function remove(): void {
    if (!removing.value) {
        return;
    }

    router.delete(destroy(removing.value.key).url, {
        preserveScroll: true,
        onStart: () => (processing.value = true),
        onFinish: () => {
            processing.value = false;
            removeOpen.value = false;
        },
    });
}
</script>

<template>
    <SectionCard
        :title="t('Bog\'langan akkauntlar')"
        :description="
            t(
                'Google yoki ORCID iD orqali bir tugma bilan kiring. ORCID iD profilingizga avtomatik yoziladi.',
            )
        "
        :icon="Link2"
    >
        <ul class="-mx-5 -my-5 divide-y divide-line">
            <li
                v-for="account in accounts"
                :key="account.key"
                class="group flex flex-wrap items-center gap-3 px-5 py-4 transition-colors hover:bg-brand-50/40"
                :data-test="`social-account-${account.key}`"
            >
                <SocialProviderIcon
                    :provider="account"
                    class="size-9 transition-transform duration-200 group-hover:scale-105"
                />

                <div class="min-w-0 flex-1">
                    <p
                        class="flex items-center gap-2 text-sm font-semibold text-navy-900"
                    >
                        {{ account.label }}
                        <span
                            v-if="account.linked"
                            class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-200"
                        >
                            <CircleCheck class="size-3" />
                            {{ t("Bog'langan") }}
                        </span>
                    </p>
                    <p class="truncate text-xs text-navy-500">
                        <template v-if="account.linked">
                            {{
                                account.key === 'orcid'
                                    ? account.identifier
                                    : (account.email ?? account.identifier)
                            }}
                            <template v-if="account.linkedAt">
                                ·
                                {{
                                    t(':date dan beri', {
                                        date: formatDate(account.linkedAt),
                                    })
                                }}
                            </template>
                        </template>
                        <template v-else>{{ t("Bog'lanmagan") }}</template>
                    </p>
                </div>

                <template v-if="account.linked">
                    <span
                        v-if="isOnlyMethod(account)"
                        class="max-w-56 text-right text-[11px] leading-snug text-amber-700"
                    >
                        {{
                            t(
                                "Uzish uchun avval parol o'rnating — bu hozir yagona kirish usulingiz",
                            )
                        }}
                    </span>
                    <button
                        v-else
                        type="button"
                        :class="[
                            secondaryButtonClass,
                            'h-9 hover:border-red-300 hover:text-red-600',
                        ]"
                        @click="askRemove(account)"
                    >
                        <Unlink class="size-4" />
                        {{ t('Uzish') }}
                    </button>
                </template>
                <a
                    v-else-if="account.enabled"
                    :href="redirect(account.key).url"
                    :class="[primaryButtonClass, 'h-9']"
                >
                    <Link2 class="size-4" />
                    {{ t("Bog'lash") }}
                </a>
            </li>
        </ul>
    </SectionCard>

    <ActionDialog
        v-model:open="removeOpen"
        :title="t('Akkauntni uzish')"
        :description="
            removing
                ? t(
                      ':provider orqali kirish o\'chiriladi. Keyin istalgan vaqtda qayta bog\'lashingiz mumkin.',
                      { provider: removing.label },
                  )
                : undefined
        "
        :icon="Unlink"
        tone="danger"
        :confirm-text="t('Uzish')"
        :processing="processing"
        @confirm="remove"
    />
</template>
