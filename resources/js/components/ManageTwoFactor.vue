<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ShieldCheck, ShieldOff } from '@lucide/vue';
import { onUnmounted, ref } from 'vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { dangerButtonClass, primaryButtonClass } from '@/lib/formStyles';
import { cn } from '@/lib/utils';
import { disable, enable } from '@/routes/two-factor';
import { t } from '@/lib/i18n';

export type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    canManageTwoFactor: false,
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => clearTwoFactorAuthData());
</script>

<template>
    <div v-if="canManageTwoFactor" class="space-y-4">
        <div
            :class="
                cn(
                    'flex flex-wrap items-center gap-4 rounded-xl p-4',
                    twoFactorEnabled
                        ? 'bg-emerald-50 ring-1 ring-emerald-200'
                        : 'bg-[#f8fafc] ring-1 ring-line',
                )
            "
        >
            <span
                :class="
                    cn(
                        'flex size-11 shrink-0 items-center justify-center rounded-full',
                        twoFactorEnabled
                            ? 'bg-white text-emerald-600'
                            : 'bg-white text-navy-400',
                    )
                "
            >
                <ShieldCheck v-if="twoFactorEnabled" class="size-5" />
                <ShieldOff v-else class="size-5" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-bold text-navy-950">
                    {{
                        twoFactorEnabled
                            ? t('Himoya yoqilgan')
                            : "Himoya o'chirilgan"
                    }}
                </p>
                <p class="mt-0.5 text-xs leading-relaxed text-navy-600">
                    {{
                        twoFactorEnabled
                            ? "Kirishda paroldan tashqari telefoningizdagi autentifikator ilovasi kodi so'raladi."
                            : "Yoqilsa, kirishda telefoningizdagi autentifikator ilovasidan (Google Authenticator va h.k.) kod so'raladi."
                    }}
                </p>
            </div>

            <template v-if="!twoFactorEnabled">
                <button
                    v-if="hasSetupData"
                    type="button"
                    :class="primaryButtonClass"
                    @click="showSetupModal = true"
                >
                    <ShieldCheck class="size-4" />
                    {{ t('Sozlashni davom ettirish') }}
                </button>
                <Form
                    v-else
                    v-bind="enable.form()"
                    @success="showSetupModal = true"
                    #default="{ processing }"
                >
                    <button
                        type="submit"
                        :class="primaryButtonClass"
                        :disabled="processing"
                    >
                        <ShieldCheck class="size-4" />
                        {{ t('Yoqish') }}
                    </button>
                </Form>
            </template>
            <Form v-else v-bind="disable.form()" #default="{ processing }">
                <button
                    type="submit"
                    :class="cn(dangerButtonClass, 'h-9')"
                    :disabled="processing"
                >
                    {{ t("O'chirish") }}
                </button>
            </Form>
        </div>

        <TwoFactorRecoveryCodes v-if="twoFactorEnabled" />

        <TwoFactorSetupModal
            v-model:isOpen="showSetupModal"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />
    </div>
</template>
