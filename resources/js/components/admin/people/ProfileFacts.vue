<script setup lang="ts">
import {
    Building2,
    CalendarDays,
    Clock,
    GraduationCap,
    Mail,
    MapPin,
    Phone,
    UserRound,
} from '@lucide/vue';
import { computed } from 'vue';
import type { Component } from 'vue';
import SectionCard from '@/components/admin/ui/SectionCard.vue';
import { formatDate, formatPhone, timeAgo } from '@/lib/format';
import type { PersonProfile, PersonSubject } from '@/types';
import { t } from '@/lib/i18n';

/** Profil ma'lumotlari: aloqa, ilmiy daraja, tashkilot, ORCID, yo'nalishlar, bio */
const props = defineProps<{
    profile: PersonProfile;
    subjects: PersonSubject[];
}>();

type Fact = { icon: Component; label: string; value: string; href?: string };

const facts = computed<Fact[]>(() => {
    const p = props.profile;
    const list: (Fact | null)[] = [
        {
            icon: Mail,
            label: t('Email'),
            value: p.email,
            href: `mailto:${p.email}`,
        },
        p.phone
            ? {
                  icon: Phone,
                  label: t('Telefon'),
                  value: formatPhone(p.phone),
                  href: `tel:${p.phone}`,
              }
            : null,
        p.degree || p.title
            ? {
                  icon: GraduationCap,
                  label: t('Ilmiy daraja / unvon'),
                  value: [p.degree, p.title].filter(Boolean).join(', '),
              }
            : null,
        p.organization || p.department
            ? {
                  icon: Building2,
                  label: t('Tashkilot'),
                  value: [p.organization, p.department]
                      .filter(Boolean)
                      .join(' — '),
              }
            : null,
        p.position
            ? { icon: UserRound, label: t('Lavozim'), value: p.position }
            : null,
        p.city || p.country
            ? {
                  icon: MapPin,
                  label: t('Manzil'),
                  value: [p.city, p.country].filter(Boolean).join(', '),
              }
            : null,
        {
            icon: CalendarDays,
            label: t("Ro'yxatdan o'tgan"),
            value: formatDate(p.createdAt),
        },
        {
            icon: Clock,
            label: t('Oxirgi kirish'),
            value: p.lastLoginAt ? timeAgo(p.lastLoginAt) : t('Kirmagan'),
        },
    ];

    return list.filter((f): f is Fact => f !== null);
});
</script>

<template>
    <SectionCard :title="t('Profil')" :icon="UserRound">
        <dl class="grid grid-cols-1 gap-3">
            <div v-for="fact in facts" :key="fact.label" class="flex gap-3">
                <span
                    class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-[#f1f5fb] text-navy-500"
                >
                    <component :is="fact.icon" class="size-4" />
                </span>
                <div class="min-w-0">
                    <dt class="text-[11px] font-medium text-navy-400">
                        {{ fact.label }}
                    </dt>
                    <dd
                        class="text-[13px] font-semibold [overflow-wrap:anywhere] text-navy-900"
                    >
                        <a
                            v-if="fact.href"
                            :href="fact.href"
                            class="transition-colors hover:text-brand-700"
                            >{{ fact.value }}</a
                        >
                        <template v-else>{{ fact.value }}</template>
                    </dd>
                </div>
            </div>

            <div v-if="profile.orcid" class="flex gap-3">
                <span
                    class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-[#eef8e4] text-[11px] font-black text-[#5d8a1f]"
                    >iD</span
                >
                <div class="min-w-0">
                    <dt class="text-[11px] font-medium text-navy-400">ORCID</dt>
                    <dd>
                        <a
                            :href="`https://orcid.org/${profile.orcid}`"
                            target="_blank"
                            rel="noopener"
                            class="text-[13px] font-semibold text-[#5d8a1f] tabular-nums transition-colors hover:text-[#466a14] hover:underline"
                            >{{ profile.orcid }}</a
                        >
                    </dd>
                </div>
            </div>
        </dl>

        <div v-if="subjects.length" class="mt-4 border-t border-line pt-4">
            <p class="mb-2 text-[11px] font-medium text-navy-400">
                {{ t("Ilmiy yo'nalishlar") }}
            </p>
            <div class="flex flex-wrap gap-1.5">
                <span
                    v-for="subject in subjects"
                    :key="subject.id"
                    class="rounded-md bg-brand-50 px-2 py-0.5 text-[11px] font-semibold text-brand-700"
                    >{{ subject.name }}</span
                >
            </div>
        </div>

        <p
            v-if="profile.bio"
            class="mt-4 border-t border-line pt-4 text-[13px] leading-relaxed whitespace-pre-line text-navy-700"
        >
            {{ profile.bio }}
        </p>
    </SectionCard>
</template>
