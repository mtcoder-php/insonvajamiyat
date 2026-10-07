<script setup lang="ts">
import { Head, Link, router, usePage, usePoll } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Bell,
    ExternalLink,
    Inbox,
    Mail,
    Megaphone,
    MessagesSquare,
    Paperclip,
    Search,
    UserRound,
    UserRoundCheck,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, ref, watch } from 'vue';
import BroadcastPanel from '@/components/admin/messages/BroadcastPanel.vue';
import NotificationsPanel from '@/components/admin/messages/NotificationsPanel.vue';
import PageHeader from '@/components/admin/ui/PageHeader.vue';
import SimplePager from '@/components/admin/ui/SimplePager.vue';
import MessageThread from '@/components/articles/MessageThread.vue';
import ArticleStatusPill from '@/components/cabinet/ArticleStatusPill.vue';
import { inputClass } from '@/lib/formStyles';
import { formatNumber, timeAgo } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/messages';
import type {
    MessageCenterProps,
    MessageCenterTab,
    StaffConversation,
    StaffInboxScope,
} from '@/types';

/**
 * Admin → Xabarlar: mualliflar bilan yozishmalar markazi (chapda ro'yxat, o'ngda yozishma),
 * ommaviy xabar va bildirishnomalar. Ochiq yozishma har 20 soniyada yangilanadi.
 */
const props = defineProps<MessageCenterProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin panel', href: dashboard() },
            { title: 'Xabarlar', href: index() },
        ],
    },
});

const page = usePage();
const notificationsUnread = computed(
    () => (page.props.notifications as { unread?: number } | null)?.unread ?? 0,
);

usePoll(20_000, () => ({
    only:
        props.filters.tab === 'messages'
            ? [
                  'conversations',
                  'thread',
                  'counts',
                  'notifications',
                  'adminBadges',
              ]
            : props.filters.tab === 'notifications'
              ? ['notificationsPage', 'notifications', 'adminBadges']
              : ['broadcasts', 'notifications'],
}));

/* ---------- Navigatsiya ---------- */

type Query = Record<string, string | number | boolean>;

function baseQuery(extra: Query = {}): Query {
    const q: Query = {};

    if (props.filters.scope !== 'all') q.scope = props.filters.scope;
    if (props.filters.q) q.q = props.filters.q;

    return { ...q, ...extra };
}

function visit(query: Query, only?: string[]): void {
    router.get(props.urls.index, query, {
        preserveScroll: true,
        preserveState: true,
        ...(only ? { only } : {}),
    });
}

function setTab(tab: MessageCenterTab): void {
    visit(tab === 'messages' ? {} : { tab });
}

function setScope(scope: StaffInboxScope): void {
    const q = baseQuery();
    delete q.scope;
    visit(scope === 'all' ? q : { ...q, scope });
}

const search = ref(props.filters.q);
let timer: ReturnType<typeof setTimeout> | undefined;

watch(search, (value) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        const q = baseQuery();
        delete q.q;
        visit(value.trim() ? { ...q, q: value.trim() } : q, [
            'conversations',
            'filters',
        ]);
    }, 350);
});

// Mobil: yozishma ochiq bo'lsa ro'yxat yashiriladi
const showList = ref(props.thread === null);

function openConversation(c: StaffConversation): void {
    showList.value = false;
    visit(baseQuery({ article: c.uuid }), [
        'thread',
        'filters',
        'conversations',
        'counts',
        'notifications',
        'adminBadges',
    ]);
}

function goPage(p: number): void {
    visit(
        baseQuery({
            ...(p > 1 ? { page: p } : {}),
            ...(props.filters.article
                ? { article: props.filters.article }
                : {}),
        }),
        ['conversations', 'filters'],
    );
}

const tabMeta = computed<
    Record<MessageCenterTab, { label: string; icon: Component; count: number }>
>(() => ({
    messages: {
        label: 'Yozishmalar',
        icon: MessagesSquare,
        count: props.counts.unread,
    },
    broadcast: { label: 'Ommaviy xabar', icon: Megaphone, count: 0 },
    notifications: {
        label: 'Bildirishnomalar',
        icon: Bell,
        count: notificationsUnread.value,
    },
}));

const scopes = computed(() => [
    { key: 'all' as const, label: 'Hammasi', count: props.counts.all },
    {
        key: 'unread' as const,
        label: 'Javob kutmoqda',
        count: props.counts.unread,
    },
    {
        key: 'mine' as const,
        label: 'Menga biriktirilgan',
        count: props.counts.mine,
    },
]);

function notificationsQuery(p = 1, unread = props.filters.unread): Query {
    const q: Query = { tab: 'notifications' };

    if (unread) q.unread = 1;
    if (p > 1) q.npage = p;

    return q;
}
</script>

<template>
    <Head title="Xabarlar" />

    <div
        class="flex flex-1 flex-col gap-5 bg-[#f5f7fb] p-4 text-navy-900 md:p-6"
    >
        <PageHeader
            title="Xabarlar"
            description="Mualliflar bilan yozishmalar, ommaviy e'lonlar va bildirishnomalar"
        >
            <template #before>
                <span
                    class="mb-2 inline-flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"
                >
                    <Mail class="size-5" />
                </span>
            </template>
        </PageHeader>

        <nav
            class="flex flex-wrap gap-1 rounded-xl border border-line bg-white p-1 shadow-[0_1px_2px_rgba(0,30,60,0.05)] sm:w-fit"
            role="tablist"
        >
            <button
                v-for="key in tabs"
                :key="key"
                type="button"
                role="tab"
                :aria-selected="filters.tab === key"
                :class="
                    cn(
                        'flex flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2 text-[13px] font-semibold transition-all sm:flex-none',
                        filters.tab === key
                            ? 'bg-brand-600 text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)]'
                            : 'text-navy-600 hover:bg-brand-50 hover:text-brand-700',
                    )
                "
                @click="setTab(key)"
            >
                <component :is="tabMeta[key].icon" class="size-4" />
                {{ tabMeta[key].label }}
                <span
                    v-if="tabMeta[key].count > 0"
                    :class="
                        cn(
                            'min-w-5 rounded-full px-1.5 text-[11px] leading-5 tabular-nums',
                            filters.tab === key
                                ? 'bg-white/25'
                                : 'bg-danger text-white',
                        )
                    "
                    >{{ tabMeta[key].count }}</span
                >
            </button>
        </nav>

        <!-- Yozishmalar -->
        <section
            v-if="filters.tab === 'messages'"
            class="grid min-h-[36rem] grid-cols-1 overflow-hidden rounded-xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)] lg:grid-cols-[24rem_minmax(0,1fr)]"
        >
            <aside
                :class="
                    cn(
                        'min-w-0 flex-col border-line lg:border-r',
                        !showList && thread ? 'hidden lg:flex' : 'flex',
                    )
                "
            >
                <div class="grid gap-2 border-b border-line p-3">
                    <label class="relative block">
                        <span class="sr-only">Qidirish</span>
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-navy-400"
                        />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Maqola, kod yoki muallif..."
                            :class="cn(inputClass, 'h-9 pl-9 text-[13px]')"
                        />
                    </label>
                    <div class="flex flex-wrap gap-1">
                        <button
                            v-for="s in scopes"
                            :key="s.key"
                            type="button"
                            :class="
                                cn(
                                    'inline-flex h-7 items-center gap-1 rounded-lg px-2.5 text-xs font-semibold transition-all',
                                    filters.scope === s.key
                                        ? 'bg-navy-900 text-white'
                                        : 'bg-[#f1f4f9] text-navy-600 hover:bg-brand-50 hover:text-brand-700',
                                )
                            "
                            @click="setScope(s.key)"
                        >
                            {{ s.label }}
                            <span class="tabular-nums opacity-70">{{
                                formatNumber(s.count)
                            }}</span>
                        </button>
                    </div>
                </div>

                <ul
                    v-if="conversations && conversations.data.length"
                    class="flex-1 divide-y divide-line overflow-y-auto lg:max-h-[40rem]"
                >
                    <li v-for="c in conversations.data" :key="c.uuid">
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
                                        class="flex min-w-0 items-center gap-1.5 text-[11px] text-navy-500"
                                    >
                                        <span class="font-mono"
                                            >#{{ c.code }}</span
                                        >
                                        <span class="truncate"
                                            >· {{ c.author }}</span
                                        >
                                        <UserRoundCheck
                                            v-if="c.isMine"
                                            class="size-3 shrink-0 text-brand-600"
                                            aria-label="Menga biriktirilgan"
                                        />
                                    </span>
                                    <span
                                        v-if="c.last"
                                        class="shrink-0 text-[11px] text-navy-400"
                                        >{{ timeAgo(c.last.createdAt) }}</span
                                    >
                                </span>
                                <span
                                    :class="
                                        cn(
                                            'mt-0.5 line-clamp-2 text-[13px] leading-snug [overflow-wrap:anywhere] text-navy-900',
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
                                            <Paperclip
                                                v-if="c.last.hasAttachment"
                                                class="mr-0.5 inline size-3"
                                            />{{
                                                c.last.mine
                                                    ? 'Siz'
                                                    : c.last.fromAuthor
                                                      ? 'Muallif'
                                                      : c.last.sender
                                            }}: {{ c.last.body }}
                                        </template>
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
                        filters.q || filters.scope !== 'all'
                            ? 'Yozishma topilmadi'
                            : "Hali yozishmalar yo'q. Maqola sahifasidan muallifga yozing."
                    }}
                </div>
                <div
                    v-if="conversations && conversations.meta.lastPage > 1"
                    class="border-t border-line px-3 py-2"
                >
                    <SimplePager :meta="conversations.meta" @go="goPage" />
                </div>
            </aside>

            <div
                :class="
                    cn(
                        'min-w-0 flex-col',
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
                            aria-label="Ro'yxatga qaytish"
                            @click="showList = true"
                        >
                            <ArrowLeft class="size-4" />
                        </button>
                        <div class="min-w-0 flex-1">
                            <p
                                class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-navy-500"
                            >
                                <span class="font-mono"
                                    >#{{ thread.code }}</span
                                >
                                <ArticleStatusPill
                                    :group="thread.statusGroup"
                                    :label="thread.statusLabel"
                                />
                            </p>
                            <h2
                                class="mt-1 truncate text-[15px] font-bold text-navy-950"
                                :title="thread.title"
                            >
                                {{ thread.title }}
                            </h2>
                            <p
                                class="mt-0.5 flex flex-wrap items-center gap-x-3 text-xs text-navy-500"
                            >
                                <span class="inline-flex items-center gap-1"
                                    ><UserRound class="size-3.5" />
                                    {{ thread.author }}
                                    <a
                                        :href="`mailto:${thread.authorEmail}`"
                                        class="text-brand-700 hover:underline"
                                        >{{ thread.authorEmail }}</a
                                    ></span
                                >
                                <span v-if="thread.editor"
                                    >Mas'ul muharrir: {{ thread.editor }}</span
                                >
                            </p>
                        </div>
                        <Link
                            :href="thread.articleUrl"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-line px-3 py-1.5 text-xs font-semibold text-navy-700 transition-all hover:-translate-y-px hover:border-brand-300 hover:text-brand-700"
                        >
                            <ExternalLink class="size-3.5" />
                            <span class="hidden sm:inline"
                                >Maqolani ochish</span
                            >
                        </Link>
                    </header>
                    <div class="flex-1 p-4 sm:p-5">
                        <MessageThread
                            :messages="thread.items"
                            :send-url="thread.sendUrl"
                            empty-text="Hali yozishma yo'q. Muallifga birinchi xabarni yozing."
                            placeholder="Muallifga javob yozing..."
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
                        Yozishmani tanlang
                    </p>
                    <p class="max-w-sm text-sm text-navy-500">
                        Chapdagi ro'yxatdan maqolani tanlang. «Javob kutmoqda» —
                        muallif yozgan va hali ochilmagan xabarlar.
                    </p>
                </div>
            </div>
        </section>

        <BroadcastPanel
            v-else-if="
                filters.tab === 'broadcast' &&
                audiences &&
                broadcasts &&
                urls.broadcast
            "
            :audiences="audiences"
            :history="broadcasts"
            :url="urls.broadcast"
        />

        <NotificationsPanel
            v-else-if="filters.tab === 'notifications' && notificationsPage"
            :page="notificationsPage"
            :only-unread="filters.unread"
            :unread="notificationsUnread"
            :read-all-url="urls.readAll"
            @filter="(u) => visit(notificationsQuery(1, u))"
            @page="(p) => visit(notificationsQuery(p))"
        />
    </div>
</template>
