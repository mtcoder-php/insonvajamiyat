<script setup lang="ts">
import { ArrowUpRight } from '@lucide/vue';
import SocialProviderIcon from '@/components/auth/SocialProviderIcon.vue';
import { t } from '@/lib/i18n';
import { redirect } from '@/routes/social';
import type { SocialProviderOption } from '@/types';

/**
 * "Google orqali kirish" / "ORCID iD orqali kirish" tugmalari (login va ro'yxatdan o'tish sahifalari).
 * Oddiy havola (Inertia emas): provayder sahifasiga to'liq o'tiladi.
 * Provayder sozlanmagan bo'lsa (config/services.php) — komponent umuman ko'rinmaydi.
 */
withDefaults(
    defineProps<{
        providers: SocialProviderOption[];
        mode?: 'login' | 'register';
    }>(),
    { mode: 'login' },
);
</script>

<template>
    <div v-if="providers.length" class="grid gap-4">
        <div
            class="flex items-center gap-3 text-xs font-medium tracking-wide text-muted-foreground uppercase"
        >
            <span class="h-px flex-1 bg-line" />
            {{ t('yoki') }}
            <span class="h-px flex-1 bg-line" />
        </div>

        <div class="grid gap-3">
            <a
                v-for="provider in providers"
                :key="provider.key"
                :href="redirect(provider.key).url"
                :data-test="`social-${provider.key}`"
                class="group relative flex h-11 items-center justify-center gap-2.5 overflow-hidden rounded-lg border border-line bg-white px-4 text-sm font-semibold text-navy-800 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-brand-300 hover:text-navy-950 hover:shadow-md hover:shadow-brand-600/10 focus-visible:ring-2 focus-visible:ring-brand-500/40 focus-visible:outline-none active:translate-y-0"
            >
                <span
                    aria-hidden="true"
                    class="pointer-events-none absolute inset-0 bg-linear-to-r from-brand-50/0 via-brand-50/70 to-brand-50/0 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                />
                <SocialProviderIcon
                    :provider="provider"
                    class="relative transition-transform duration-200 group-hover:scale-110"
                />
                <span class="relative whitespace-nowrap">
                    {{
                        mode === 'register'
                            ? t(":provider orqali ro'yxatdan o'tish", {
                                  provider: provider.label,
                              })
                            : t(':provider orqali kirish', {
                                  provider: provider.label,
                              })
                    }}
                </span>
                <ArrowUpRight
                    class="relative size-3.5 -translate-x-1 text-brand-600 opacity-0 transition-all duration-200 group-hover:translate-x-0 group-hover:opacity-100"
                />
            </a>
        </div>
    </div>
</template>
