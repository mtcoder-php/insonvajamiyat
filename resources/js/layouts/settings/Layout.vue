<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Palette, ShieldCheck, UserRound } from '@lucide/vue';
import { computed } from 'vue';
import UserAvatar from '@/components/users/UserAvatar.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { primaryRoleLabel } from '@/lib/roles';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem, User } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Shaxsiy sozlamalar: profil, xavfsizlik, ko'rinish.
 * Yuqorida foydalanuvchi kartasi va bo'limlar (tab) menyusi.
 */
const { isCurrentOrParentUrl } = useCurrentUrl();
const page = usePage();

// "Ko'rinish" (yorug'/qorong'i mavzu) — faqat admin panel uchun; muallif kabineti yorug'
const tabs = computed<NavItem[]>(() => [
    { title: t('Profil'), href: editProfile(), icon: UserRound },
    { title: t('Xavfsizlik'), href: editSecurity(), icon: ShieldCheck },
    ...(page.props.auth.isStaff
        ? [
              {
                  title: t("Ko'rinish"),
                  href: editAppearance(),
                  icon: Palette,
              },
          ]
        : []),
]);

const user = computed(() => page.props.auth.user as User);
const role = computed(() => primaryRoleLabel(page.props.auth.roles));
</script>

<template>
    <div
        :class="
            cn(
                'flex flex-1 flex-col gap-5',
                // Admin panelda o'z foni va chegarasi; muallif kabinetida layout beradi
                page.props.auth.isStaff && 'bg-[#f5f7fb] p-4 md:p-6',
            )
        "
    >
        <section
            class="relative isolate overflow-hidden rounded-2xl bg-navy-950 px-5 py-5 text-white shadow-[0_16px_36px_-22px_rgba(0,30,60,0.8)] sm:px-6"
        >
            <div
                class="absolute inset-0 -z-10 bg-girih opacity-[0.07]"
                aria-hidden="true"
            />
            <div
                class="absolute -top-24 right-0 -z-10 size-72 rounded-full bg-brand-500/30 blur-3xl"
                aria-hidden="true"
            />

            <div class="flex flex-wrap items-center gap-4">
                <UserAvatar
                    :name="user.name"
                    :url="user.avatar"
                    size="lg"
                    class="ring-white/20"
                />
                <div class="min-w-0 flex-1">
                    <p
                        class="text-xs font-medium tracking-wider text-gold-300 uppercase"
                    >
                        {{ t('Shaxsiy sozlamalar') }}
                    </p>
                    <h1
                        class="mt-0.5 truncate font-sans text-xl font-bold text-white sm:text-2xl"
                    >
                        {{ user.name }}
                    </h1>
                    <p class="truncate text-sm text-white/70">
                        {{ role }} · {{ user.email }}
                    </p>
                </div>
            </div>

            <nav
                class="mt-5 -mb-1 flex gap-1 overflow-x-auto"
                :aria-label="t('Sozlamalar bo\'limlari')"
            >
                <Link
                    v-for="tab in tabs"
                    :key="toUrl(tab.href)"
                    :href="tab.href"
                    :class="
                        cn(
                            'inline-flex shrink-0 items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-semibold transition-all',
                            isCurrentOrParentUrl(tab.href)
                                ? 'bg-white text-navy-950 shadow-sm'
                                : 'text-white/75 hover:bg-white/10 hover:text-white',
                        )
                    "
                >
                    <component :is="tab.icon" class="size-4" />
                    {{ tab.title }}
                </Link>
            </nav>
        </section>

        <slot />
    </div>
</template>
