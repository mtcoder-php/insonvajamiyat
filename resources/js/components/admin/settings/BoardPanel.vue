<script setup lang="ts">
import {
    Crown,
    ExternalLink,
    PenLine,
    Plus,
    Trash2,
    UserRound,
    UsersRound,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { about } from '@/routes';
import type { SettingsBoardMember, SettingsBoardRole } from '@/types';
import BoardMemberDialog from './BoardMemberDialog.vue';
import DeleteDialog from './DeleteDialog.vue';

/** Tahririyat kengashi — rollar bo'yicha guruhlangan a'zo kartochkalari */
const props = defineProps<{
    board: SettingsBoardMember[];
    roles: { value: SettingsBoardRole; label: string }[];
    storeUrl: string;
}>();

const groups = computed(() =>
    props.roles.map((role) => ({
        ...role,
        items: props.board.filter((m) => m.role === role.value),
    })),
);

const editing = ref<SettingsBoardMember | null>(null);
const formOpen = ref(false);
const newRole = ref<SettingsBoardRole>('member');
const removing = ref<SettingsBoardMember | null>(null);
const deleteOpen = ref(false);

function open(
    member: SettingsBoardMember | null,
    role: SettingsBoardRole = 'member',
): void {
    editing.value = member;
    newRole.value = role;
    formOpen.value = true;
}

function remove(member: SettingsBoardMember): void {
    removing.value = member;
    deleteOpen.value = true;
}

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}
</script>

<template>
    <section class="grid grid-cols-1 gap-6">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-sans text-[15px] font-bold text-navy-950">
                    {{ t('Tahririyat kengashi') }}
                </h2>
                <p class="text-xs text-navy-500">
                    {{
                        t(
                            "«Jurnal haqida» sahifasida rollar bo'yicha ko'rinadi",
                        )
                    }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a
                    :href="`${about().url}#editorial-board`"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex h-9 items-center gap-2 rounded-lg border border-line bg-white px-3.5 text-[13px] font-semibold text-navy-700 transition-all hover:-translate-y-px hover:border-brand-200 hover:text-brand-700"
                >
                    <ExternalLink class="size-4" /> {{ t('Saytda') }}
                </a>
                <button
                    type="button"
                    class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-600 px-4 text-[13px] font-semibold text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)] transition-all hover:-translate-y-px hover:bg-brand-500"
                    @click="open(null)"
                >
                    <Plus class="size-4" /> {{ t("A'zo qo'shish") }}
                </button>
            </div>
        </header>

        <div v-for="group in groups" :key="group.value">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h3
                    class="inline-flex items-center gap-2 text-[13px] font-bold text-navy-700"
                >
                    <component
                        :is="group.value === 'member' ? UsersRound : Crown"
                        class="size-4 text-brand-600"
                    />
                    {{ group.label }}
                    <span
                        class="rounded-full bg-[#eef3fa] px-2 text-[11px] text-navy-500 tabular-nums"
                        >{{ group.items.length }}</span
                    >
                </h3>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 transition-colors hover:text-brand-500"
                    @click="open(null, group.value)"
                >
                    <Plus class="size-3.5" /> {{ t("Qo'shish") }}
                </button>
            </div>

            <div
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4"
            >
                <article
                    v-for="member in group.items"
                    :key="member.id"
                    class="group flex items-start gap-3 rounded-xl border border-line bg-white p-3.5 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-[0_16px_34px_-22px_rgba(0,36,66,0.45)]"
                >
                    <span
                        :class="
                            cn(
                                'relative flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-navy-900 font-serif text-base font-bold text-gold-300 transition-transform duration-300 group-hover:scale-105',
                                !member.isActive && 'opacity-50 grayscale',
                            )
                        "
                    >
                        <img
                            v-if="member.photoUrl"
                            :src="member.photoUrl"
                            :alt="member.name"
                            loading="lazy"
                            class="size-full object-cover"
                        />
                        <template v-else-if="member.name">{{
                            initials(member.name)
                        }}</template>
                        <UserRound v-else class="size-6" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p
                            class="text-[13px] leading-snug font-bold text-navy-950 transition-colors group-hover:text-brand-700"
                        >
                            {{ member.name }}
                        </p>
                        <p
                            class="mt-0.5 line-clamp-2 text-[11px] text-navy-500"
                        >
                            {{
                                member.translations.academic_degree.uz ||
                                member.translations.position.uz ||
                                member.translations.organization.uz ||
                                '—'
                            }}
                        </p>
                        <span
                            v-if="!member.isActive"
                            class="mt-1.5 inline-block rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600"
                            >{{ t('Nofaol') }}</span
                        >
                    </div>
                    <span class="inline-flex shrink-0 flex-col gap-0.5">
                        <button
                            type="button"
                            class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-brand-50 hover:text-brand-700"
                            :aria-label="t('Tahrirlash')"
                            @click="open(member)"
                        >
                            <PenLine class="size-4" />
                        </button>
                        <button
                            type="button"
                            class="inline-flex size-8 items-center justify-center rounded-lg text-navy-500 transition-colors hover:bg-red-50 hover:text-red-600"
                            :aria-label="t('O\'chirish')"
                            @click="remove(member)"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </span>
                </article>
            </div>
            <p
                v-if="!group.items.length"
                class="rounded-xl border border-dashed border-line bg-white px-4 py-6 text-center text-sm text-navy-400"
            >
                {{ t("Hali qo'shilmagan") }}
            </p>
        </div>

        <BoardMemberDialog
            v-model:open="formOpen"
            :member="editing"
            :roles="roles"
            :default-role="newRole"
            :store-url="storeUrl"
        />
        <DeleteDialog
            v-model:open="deleteOpen"
            :url="removing?.urls.destroy ?? null"
            :title="t('A\'zoni o\'chirish')"
            :description="
                t('«:name» va uning rasmi o\'chiriladi.', {
                    name: removing?.name ?? '',
                })
            "
        />
    </section>
</template>
