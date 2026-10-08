<script setup lang="ts">
import { Ban, BadgeCheck } from '@lucide/vue';
import UserAvatar from '@/components/users/UserAvatar.vue';
import type { PersonProfile } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Muallif / taqrizchi sahifasi sarlavhasi: banner, avatar, ism, lavozim va tashkilot,
 * belgilar (`badges` slot) va amallar (`actions` slot).
 */
defineProps<{ profile: PersonProfile }>();
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
    >
        <div class="relative isolate h-14 bg-navy-950 sm:h-16">
            <div
                class="absolute inset-0 -z-10 bg-girih opacity-[0.07]"
                aria-hidden="true"
            />
            <div
                class="absolute -top-24 right-10 -z-10 size-56 rounded-full bg-brand-500/30 blur-3xl"
                aria-hidden="true"
            />
            <div
                class="absolute -bottom-28 left-1/3 -z-10 size-48 rounded-full bg-gold-500/15 blur-3xl"
                aria-hidden="true"
            />
        </div>
        <div
            class="flex flex-col gap-3 px-5 pb-4 sm:px-6 lg:flex-row lg:items-center"
        >
            <div
                class="relative z-10 -mt-8 shrink-0 self-center rounded-full bg-white ring-4 ring-white lg:-mt-7 lg:self-start"
            >
                <UserAvatar
                    :name="profile.name"
                    :url="profile.avatarUrl"
                    size="lg"
                />
            </div>
            <div class="min-w-0 flex-1 text-center lg:pt-2 lg:text-left">
                <h1
                    class="inline-flex items-center gap-1.5 font-sans text-xl font-bold tracking-tight [overflow-wrap:anywhere] text-navy-950"
                >
                    {{ profile.name }}
                    <BadgeCheck
                        v-if="profile.orcid"
                        class="size-5 shrink-0 text-emerald-500"
                        :aria-label="t('ORCID bog\'langan')"
                    />
                </h1>
                <p
                    v-if="profile.organization || profile.position"
                    class="mt-0.5 text-sm [overflow-wrap:anywhere] text-navy-500"
                >
                    {{
                        [profile.position, profile.organization]
                            .filter(Boolean)
                            .join(' · ')
                    }}
                </p>
                <div
                    class="mt-2 flex flex-wrap items-center justify-center gap-1.5 lg:justify-start"
                >
                    <span
                        v-if="profile.isBlocked"
                        class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-semibold text-red-700 ring-1 ring-red-200 ring-inset"
                    >
                        <Ban class="size-3" /> {{ t('Bloklangan') }}
                    </span>
                    <slot name="badges" />
                </div>
            </div>
            <div
                class="flex flex-wrap items-center justify-center gap-2 lg:justify-end lg:pt-2"
            >
                <slot name="actions" />
            </div>
        </div>
    </section>
</template>
