<script setup lang="ts">
import { Head, Link, router, usePoll } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Bell,
    BellOff,
    CheckCheck,
    ExternalLink,
    Inbox,
    Mail,
    MessagesSquare,
    Search,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import SimplePager from '@/components/admin/ui/SimplePager.vue';
import MessageThread from '@/components/articles/MessageThread.vue';
import CabinetPageHeader from '@/components/cabinet/CabinetPageHeader.vue';
import { inputClass } from '@/lib/formStyles';
import { formatDate, formatTime, timeAgo } from '@/lib/format';
import { cn } from '@/lib/utils';
import { index } from '@/routes/cabinet/messages';
import { readAll } from '@/routes/notifications';
import type {
    InboxConversation,
    InboxThread,
    NotificationItem,
    SimpleMeta,
} from '@/types';
import { t } from '@/lib/i18n';

/**
 * Muallif kabineti → Xabarlar (TZ 4.1.3):
 *  - "Yozishmalar": chapda maqolalar ro'yxati (oxirgi xabar, o'qilmaganlar), o'ngda tahririyat bilan yozishma;
 *  - "Bildirishnomalar": barcha bildirishnomalar, o'qilmaganlar filtri.
 * Ochiq yozishma har 20 soniyada yangilanadi (usePoll).
 */
const props = defineProps<{
    filters: {
        tab: 'messages' | 'notifications';
        article: string | null;
        unread: boolean;
    };
    conversations: InboxConversation[];
    thread: InboxThread | null;
    notificationsPage: { data: NotificationItem[]; meta: SimpleMeta } | null;
    unreadMessages: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Xabarlar', href: index() }],
    },
});

usePoll(20_000, () => ({
    only:
        props.filters.tab === 'messages'
            ? ['conversations', 'thread', 'unreadMessages', 'notifications']
            : ['notificationsPage', 'notifications'],
}));

const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();

    return q === ''
        ? props.conversations
        : props.conversations.filter(
              (c) =>
                  c.title.toLowerCase().includes(q) ||
                  c.code.toLowerCase().includes(q),
          );
});

// Mobil: yozishma ochiq bo'lsa ro'yxat yashiriladi
const showList = ref(props.filters.article === null);

function go(query: Record<string, string | number | boolean>): void {
    router.get(index.url(), query, { preserveScroll: true });
}

function openConversation(c: InboxConversation): void {
    showList.value = false;
    router.get(
        index.url(),
        { article: c.uuid },
        {
            preserveScroll: true,
            preserveState: true,
            only: [
                'thread',
                'filters',
                'conversations',
                'unreadMessages',
                'notifications',
            ],
        },
    );
}

function setTab(tab: 'messages' | 'notifications'): void {
    go(tab === 'messages' ? {} : { tab });
}

function notificationsQuery(page = 1, unread = props.filters.unread) {
    const q: Record<string, string | number> = { tab: 'notifications' };

    if (unread) q.unread = 1;
    if (page > 1) q.npage = page;

    return q;
}

function markAll(): void {
    router.post(readAll.url(), {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('Xabarlar')" />

    <div class="flex flex-col gap-5">
        <CabinetPageHeader
            :title="t('Xabarlar')"
            :description="
                t(
                    'Tahririyat bilan yozishmalar va maqolalaringiz bo\'yicha bildirishnomalar.',
                )
            "
            :icon="Mail"
            :breadcrumbs="[{ title: t('Xabarlar'), href: index() }]"
        />

        <nav
            class="flex gap-1 rounded-xl border border-line bg-white p-1 shadow-[0_1px_2px_rgba(0,30,60,0.05)] sm:w-fit"
            role="tablist"
        >
            <button
                v-for="t in [
                    {
                        key: 'messages',
                        label: t('Yozishmalar'),
                        icon: MessagesSquare,
                        count: unreadMessages,
                    },
                    {
                        key: 'notifications',
                        label: t('Bildirishnomalar'),
                        icon: Bell,
                        count: $page.props.notifications?.unread ?? 0,
                    },
                ] as const"
                :key="t.key"
                type="button"
                role="tab"
                :aria-selected="filters.tab === t.key"
                :class="
                    cn(
                        'flex flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2 text-[13px] font-semibold transition-all sm:flex-none',
                        filters.tab === t.key
                            ? 'bg-brand-600 text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)]'
                            : 'text-navy-600 hover:bg-brand-50 hover:text-brand-700',
                    )
                "
                @click="setTab(t.key)"
            >
                <component :is="t.icon" class="size-4" />
                {{ t.label }}
                <span
                    v-if="t.count > 0"
                    :class="
                        cn(
                            'min-w-5 rounded-full px-1.5 text-[11px] leading-5 tabular-nums',
                            filters.tab === t.key
                                ? 'bg-white/25'
                                : 'bg-danger text-white',
                        )
                    "
                    >{{ t.count }}</span
                >
            </button>
        </nav>

        <!-- Yozishmalar -->
        <section
            v-if="filters.tab === 'messages'"
            class="grid min-h-[34rem] overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)] lg:grid-cols-[22rem_minmax(0,1fr)]"
        >
            <aside
                :class="
                    cn(
                        'flex flex-col border-line lg:border-r',
                        !showList && thread ? 'hidden lg:flex' : 'flex',
                    )
                "
            >
                <div class="border-b border-line p-3">
                    <label class="relative block">
                        <span class="sr-only">{{
                            t('Maqolani qidirish')
                        }}</span>
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                        />
                        <input
                            v-model="search"
                            type="search"
                            :placeholder="t('Maqola nomi yoki kodi...')"
                            :class="cn(inputClass, 'h-9 pl-9 text-[13px]')"
                        />
                    </label>
                </div>
                <ul
                    v-if="filtered.length"
                    class="flex-1 divide-y divide-line overflow-y-auto lg:max-h-[38rem]"
                >
                    <li v-for="c in filtered" :key="c.uuid">
                        <button
                            type="button"
                            :class="
                                cn(
                                    'group flex w-full gap-3 px-4 py-3 text-left transition-colors',
                                    thread?.uuid === c.uuid
                                        ? 'bg-brand-50/70 shadow-[inset_3px_0_0_var(--color-brand-600)]'
                                        : 'hover:bg-[#f5f8fc]',
                                )
                            "
                            @click="openConversation(c)"
                        >
                            <span
                                :class="
                                    cn(
                                        'flex size-10 shrink-0 items-center justify-center rounded-full transition-transform duration-300 group-hover:scale-105',
                                        c.unread > 0
                                            ? 'bg-brand-600 text-white'
                                            : 'bg-navy-50 text-navy-500',
                                    )
                                "
                            >
                                <MessagesSquare class="size-[18px]" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span
                                    class="flex items-center justify-between gap-2"
                                >
                                    <span
                                        class="font-mono text-[11px] text-navy-500"
                                        >#{{ c.code }}</span
                                    >
                                    <span
                                        v-if="c.last"
                                        class="shrink-0 text-[11px] text-navy-400"
                                        >{{ timeAgo(c.last.createdAt) }}</span
                                    >
                                </span>
                                <span
                                    :class="
                                        cn(
                                            'mt-0.5 line-clamp-2 text-[13px] leading-snug text-navy-900',
                                            c.unread > 0 && 'font-bold',
                                        )
                                    "
                                    >{{ c.title }}</span
                                >
                                <span class="mt-1 flex items-center gap-2">
                                    <span
                                        class="min-w-0 flex-1 truncate text-xs text-navy-500"
                                    >
                                        <template v-if="c.last">
                                            {{
                                                c.last.mine
                                                    ? t('Siz: ')
                                                    : t('Tahririyat: ')
                                            }}{{ c.last.body }}
                                        </template>
                                        <span
                                            v-else
                                            class="text-navy-400 italic"
                                            >{{ t("Xabar yo'q") }}</span
                                        >
                                    </span>
                                    <span
                                        v-if="c.unread > 0"
                                        class="min-w-5 rounded-full bg-danger px-1.5 text-center text-[11px] leading-5 font-bold text-white tabular-nums"
                                        >{{ c.unread }}</span
                                    >
                                </span>
                            </span>
                        </button>
                    </li>
                </ul>
                <div
                    v-else
                    class="flex flex-1 flex-col items-center justify-center gap-2 px-6 py-12 text-center text-sm text-navy-500"
                >
                    <Inbox class="size-8 text-navy-300" />
                    {{
                        conversations.length
                            ? 'Maqola topilmadi'
                            : "Yuborilgan maqolalaringiz bo'lganda yozishmalar shu yerda ko'rinadi."
                    }}
                </div>
            </aside>

            <div
                :class="
                    cn(
                        'flex min-w-0 flex-col',
                        showList || !thread ? 'hidden lg:flex' : 'flex',
                    )
                "
            >
                <template v-if="thread">
                    <header
                        class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3.5"
                    >
                        <button
                            type="button"
                            class="flex size-8 items-center justify-center rounded-lg text-navy-600 hover:bg-brand-50 lg:hidden"
                            :aria-label="t('Ro\'yxatga qaytish')"
                            @click="showList = true"
                        >
                            <ArrowLeft class="size-4" />
                        </button>
                        <div class="min-w-0 flex-1">
                            <p class="font-mono text-[11px] text-navy-500">
                                #{{ thread.code }} ·
                                <span class="font-sans">{{
                                    thread.statusLabel
                                }}</span>
                            </p>
                            <h2
                                class="truncate text-[15px] font-bold text-navy-950"
                                :title="thread.title"
                            >
                                {{ thread.title }}
                            </h2>
                        </div>
                        <Link
                            :href="thread.articleUrl"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-line px-3 py-1.5 text-xs font-semibold text-navy-700 transition-all hover:-translate-y-px hover:border-brand-300 hover:text-brand-700"
                        >
                            <ExternalLink class="size-3.5" />
                            <span class="hidden sm:inline">{{
                                t('Maqolani ochish')
                            }}</span>
                        </Link>
                    </header>
                    <div class="flex-1 p-4 sm:p-5">
                        <MessageThread
                            :messages="thread.items"
                            :send-url="thread.sendUrl"
                            :empty-text="
                                t(
                                    'Hali yozishma yo\'q. Savolingiz bo\'lsa, tahririyatga yozing.',
                                )
                            "
                        />
                    </div>
                </template>
                <div
                    v-else
                    class="flex flex-1 flex-col items-center justify-center gap-3 p-10 text-center"
                >
                    <span
                        class="flex size-14 items-center justify-center rounded-full bg-brand-50 text-brand-600"
                    >
                        <MessagesSquare class="size-7" />
                    </span>
                    <p class="font-semibold text-navy-800">
                        {{ t('Yozishmani tanlang') }}
                    </p>
                    <p class="max-w-sm text-sm text-navy-500">
                        {{
                            t(
                                "Chapdagi ro'yxatdan maqolani tanlang — tahririyat bilan yozishma shu yerda ochiladi.",
                            )
                        }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Bildirishnomalar -->
        <section
            v-else
            class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <header
                class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-3"
            >
                <div class="flex gap-1">
                    <button
                        v-for="opt in [
                            { unread: false, label: t('Barchasi') },
                            { unread: true, label: t('O\'qilmaganlar') },
                        ]"
                        :key="opt.label"
                        type="button"
                        :class="
                            cn(
                                'rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors',
                                filters.unread === opt.unread
                                    ? 'bg-navy-900 text-white'
                                    : 'text-navy-600 hover:bg-brand-50 hover:text-brand-700',
                            )
                        "
                        @click="go(notificationsQuery(1, opt.unread))"
                    >
                        {{ opt.label }}
                    </button>
                </div>
                <button
                    v-if="($page.props.notifications?.unread ?? 0) > 0"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-700 transition-colors hover:bg-brand-50"
                    @click="markAll"
                >
                    <CheckCheck class="size-4" />
                    {{ t("Hammasini o'qilgan deb belgilash") }}
                </button>
            </header>

            <ul
                v-if="notificationsPage && notificationsPage.data.length"
                class="divide-y divide-line"
            >
                <li v-for="n in notificationsPage.data" :key="n.id">
                    <button
                        type="button"
                        :class="
                            cn(
                                'group flex w-full items-start gap-3 px-5 py-3.5 text-left transition-colors hover:bg-brand-50/40',
                                !n.read && 'bg-brand-50/30',
                            )
                        "
                        @click="router.post(n.openUrl)"
                    >
                        <span
                            :class="
                                cn(
                                    'mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full transition-transform group-hover:scale-110',
                                    n.read
                                        ? 'bg-navy-50 text-navy-400'
                                        : 'bg-brand-600 text-white',
                                )
                            "
                        >
                            <Bell class="size-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span
                                class="flex items-start justify-between gap-3"
                            >
                                <span
                                    :class="
                                        cn(
                                            'text-[13px] text-navy-900',
                                            !n.read && 'font-bold',
                                        )
                                    "
                                    >{{ n.title }}</span
                                >
                                <span
                                    class="shrink-0 text-[11px] text-navy-400 tabular-nums"
                                >
                                    {{ formatDate(n.createdAt) }}
                                    {{ formatTime(n.createdAt) }}
                                </span>
                            </span>
                            <span
                                v-if="n.articleTitle"
                                class="mt-0.5 block truncate text-xs text-brand-700"
                            >
                                {{ n.articleTitle }}
                            </span>
                            <span
                                v-if="n.body"
                                class="mt-0.5 line-clamp-2 text-xs whitespace-pre-line text-navy-500"
                            >
                                {{ n.body }}
                            </span>
                        </span>
                    </button>
                </li>
            </ul>
            <div
                v-else
                class="flex flex-col items-center gap-2 px-6 py-14 text-center text-sm text-navy-500"
            >
                <BellOff class="size-8 text-navy-300" />
                {{
                    filters.unread
                        ? "O'qilmagan bildirishnomalar yo'q"
                        : "Hozircha bildirishnomalar yo'q"
                }}
            </div>

            <div
                v-if="notificationsPage"
                class="border-t border-line px-5 py-3"
            >
                <SimplePager
                    :meta="notificationsPage.meta"
                    @go="(p) => go(notificationsQuery(p))"
                />
            </div>
        </section>
    </div>
</template>
