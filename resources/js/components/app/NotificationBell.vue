<script setup lang="ts">
import { router, usePage, usePoll } from '@inertiajs/vue3';
import {
    Bell,
    BellOff,
    BookCheck,
    CheckCheck,
    ClipboardPen,
    FilePlus2,
    Gavel,
    Info,
    Wallet,
    LoaderCircle,
    MessageSquareText,
    RotateCcw,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, ref, watch } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { timeAgo } from '@/lib/format';
import { cn } from '@/lib/utils';
import { readAll } from '@/routes/notifications';
import type { NotificationItem } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Header'dagi qo'ng'iroqcha (admin panel va kabinet):
 *  - o'qilmaganlar soni va so'nggi bildirishnomalar (HandleInertiaRequests → notifications);
 *  - har 30 soniyada faqat shu ma'lumot yangilanadi (usePoll, partial reload);
 *  - yangi bildirishnoma kelsa qo'ng'iroqcha "silkinadi";
 *  - bosilganda o'qilgan bo'ladi va tegishli sahifa ochiladi.
 */
const props = withDefaults(defineProps<{ tone?: 'glass' | 'light' }>(), {
    tone: 'glass',
});

const page = usePage();
const summary = computed(() => page.props.notifications);
const unread = computed(() => summary.value?.unread ?? 0);
const items = computed<NotificationItem[]>(() => summary.value?.items ?? []);
const badge = computed(() =>
    unread.value > 99 ? '99+' : String(unread.value),
);

usePoll(30_000, { only: ['notifications'] });

const ring = ref(false);

watch(unread, (now, before) => {
    if (now > before) {
        ring.value = true;
        setTimeout(() => (ring.value = false), 1600);
    }
});

const meta: Record<string, { icon: Component; tint: string }> = {
    message: { icon: MessageSquareText, tint: 'bg-brand-50 text-brand-600' },
    decision: { icon: Gavel, tint: 'bg-violet-50 text-violet-600' },
    resubmitted: { icon: RotateCcw, tint: 'bg-amber-50 text-amber-600' },
    proof: { icon: BookCheck, tint: 'bg-teal-50 text-teal-600' },
    submitted: { icon: FilePlus2, tint: 'bg-emerald-50 text-emerald-600' },
    review: { icon: ClipboardPen, tint: 'bg-orange-50 text-orange-600' },
    payment: { icon: Wallet, tint: 'bg-emerald-50 text-emerald-600' },
};
const fallback = { icon: Info, tint: 'bg-navy-50 text-navy-500' };

const open = ref(false);
const busy = ref<string | null>(null);

function visit(item: NotificationItem): void {
    busy.value = item.id;
    router.post(
        item.openUrl,
        {},
        {
            onFinish: () => {
                busy.value = null;
                open.value = false;
            },
        },
    );
}

function markAll(): void {
    busy.value = 'all';
    router.post(
        readAll.url(),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            only: ['notifications', 'adminBadges'],
            onFinish: () => (busy.value = null),
        },
    );
}

const triggerClass = computed(() =>
    props.tone === 'glass'
        ? 'text-white/85 hover:bg-white/10 hover:text-white data-[state=open]:bg-white/15'
        : 'text-navy-700 hover:bg-brand-50 hover:text-brand-700 data-[state=open]:bg-brand-50',
);
</script>

<template>
    <DropdownMenu v-model:open="open">
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                :class="
                    cn(
                        'relative flex size-10 items-center justify-center rounded-full transition-colors outline-none focus-visible:ring-2 focus-visible:ring-brand-300',
                        triggerClass,
                    )
                "
                :aria-label="
                    t('Bildirishnomalar: :count ta o\'qilmagan', {
                        count: unread,
                    })
                "
            >
                <Bell :class="cn('size-5', ring && 'animate-bell')" />
                <span
                    v-if="unread > 0"
                    :class="
                        cn(
                            'absolute top-1 right-1 flex min-w-4.5 items-center justify-center rounded-full bg-danger px-1 text-[10px] leading-4.5 font-bold text-white ring-2',
                            tone === 'glass' ? 'ring-navy-950' : 'ring-white',
                        )
                    "
                >
                    {{ badge }}
                </span>
            </button>
        </DropdownMenuTrigger>

        <DropdownMenuContent
            align="end"
            :side-offset="8"
            class="w-[min(24rem,calc(100vw-1.5rem))] overflow-hidden rounded-xl border border-t-[3px] border-[#dbe7f5] border-t-brand-600 bg-white p-0 text-navy-900 [color-scheme:light] shadow-[0_24px_56px_-20px_rgba(0,30,60,0.45),0_0_0_1px_rgba(0,36,66,0.04)]"
        >
            <header
                class="flex items-center justify-between gap-3 border-b border-[#dbe7f5] bg-gradient-to-b from-[#f3f8ff] to-white px-4 py-3"
            >
                <div>
                    <p class="font-serif text-base font-bold text-navy-950">
                        {{ t('Bildirishnomalar') }}
                    </p>
                    <p class="text-[11px] text-navy-500">
                        {{
                            unread > 0
                                ? t(":count ta o'qilmagan", { count: unread })
                                : t("Hammasi o'qilgan")
                        }}
                    </p>
                </div>
                <button
                    v-if="unread > 0"
                    type="button"
                    :disabled="busy !== null"
                    class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-semibold text-brand-700 transition-colors hover:bg-brand-50 disabled:opacity-50"
                    @click.prevent="markAll"
                >
                    <LoaderCircle
                        v-if="busy === 'all'"
                        class="size-3.5 animate-spin"
                    />
                    <CheckCheck v-else class="size-3.5" />
                    {{ t("Hammasini o'qish") }}
                </button>
            </header>

            <ul
                v-if="items.length"
                class="max-h-[min(26rem,70vh)] divide-y divide-[#e8eff8] overflow-y-auto"
            >
                <li v-for="item in items" :key="item.id">
                    <button
                        type="button"
                        :disabled="busy !== null"
                        :class="
                            cn(
                                'group flex w-full items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-brand-50 disabled:cursor-wait',
                                item.read
                                    ? 'bg-white'
                                    : 'bg-[#f5f9ff] shadow-[inset_3px_0_0_var(--color-brand-500)]',
                            )
                        "
                        @click="visit(item)"
                    >
                        <span
                            :class="
                                cn(
                                    'flex size-9 shrink-0 items-center justify-center rounded-full transition-transform duration-300 group-hover:scale-110',
                                    (meta[item.kind] ?? fallback).tint,
                                )
                            "
                        >
                            <LoaderCircle
                                v-if="busy === item.id"
                                class="size-4 animate-spin"
                            />
                            <component
                                :is="(meta[item.kind] ?? fallback).icon"
                                v-else
                                class="size-4"
                            />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span
                                class="flex items-start justify-between gap-2"
                            >
                                <span
                                    :class="
                                        cn(
                                            'text-[13px] leading-snug text-navy-900',
                                            !item.read && 'font-semibold',
                                        )
                                    "
                                >
                                    {{ item.title }}
                                </span>
                                <span
                                    v-if="!item.read"
                                    class="mt-1.5 size-2 shrink-0 rounded-full bg-brand-500"
                                    :aria-label="t('O\'qilmagan')"
                                />
                            </span>
                            <span
                                v-if="item.articleTitle"
                                class="mt-0.5 block truncate text-xs text-brand-700/90"
                            >
                                {{ item.articleTitle }}
                            </span>
                            <span
                                v-if="item.body"
                                class="mt-0.5 line-clamp-2 text-xs text-navy-500"
                            >
                                {{ item.body }}
                            </span>
                            <span class="mt-1 block text-[11px] text-navy-400">
                                {{ timeAgo(item.createdAt) }}
                            </span>
                        </span>
                    </button>
                </li>
            </ul>
            <div
                v-else
                class="flex flex-col items-center gap-2 px-6 py-10 text-center text-sm text-navy-500"
            >
                <BellOff class="size-7 text-navy-300" />
                {{ t("Hozircha bildirishnomalar yo'q") }}
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

<style scoped>
@keyframes bell-ring {
    0%,
    100% {
        transform: rotate(0);
    }
    15%,
    45% {
        transform: rotate(14deg);
    }
    30%,
    60% {
        transform: rotate(-12deg);
    }
    75% {
        transform: rotate(6deg);
    }
}

.animate-bell {
    animation: bell-ring 0.9s ease-in-out 2;
    transform-origin: 50% 10%;
}
</style>
