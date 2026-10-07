/* ------------------------------------------------------------------
 * Admin → Xabarlar — App\Services\Messages\{StaffInbox, BroadcastService}
 * ------------------------------------------------------------------ */

import type { ArticleStatusGroup } from './cabinet';
import type { NotificationItem } from './journal';
import type { SimpleMeta } from './reports';
import type { ThreadMessage } from './messages';

export type StaffInboxScope = 'all' | 'unread' | 'mine';

export type StaffConversation = {
    uuid: string;
    code: string;
    title: string;
    author: string;
    editor: string | null;
    isMine: boolean;
    statusLabel: string;
    statusGroup: ArticleStatusGroup;
    unread: number;
    last: {
        body: string;
        sender: string;
        fromAuthor: boolean;
        mine: boolean;
        hasAttachment: boolean;
        createdAt: string;
    } | null;
};

export type StaffThread = {
    uuid: string;
    code: string;
    title: string;
    statusLabel: string;
    statusGroup: ArticleStatusGroup;
    author: string;
    authorEmail: string;
    editor: string | null;
    articleUrl: string;
    items: ThreadMessage[];
    sendUrl: string | null;
};

export type BroadcastAudienceOption = {
    value: string;
    label: string;
    count: number;
};

export type BroadcastItem = {
    uuid: string;
    subject: string;
    body: string;
    audience: string;
    sendEmail: boolean;
    status: 'queued' | 'sending' | 'sent' | 'failed';
    recipients: number;
    sent: number;
    sender: string;
    createdAt: string | null;
    sentAt: string | null;
    error: string | null;
};

export type MessageCenterTab = 'messages' | 'broadcast' | 'notifications';

export type MessageCenterProps = {
    tabs: MessageCenterTab[];
    filters: {
        tab: MessageCenterTab;
        scope: StaffInboxScope;
        q: string;
        article: string | null;
        unread: boolean;
    };
    conversations: { data: StaffConversation[]; meta: SimpleMeta } | null;
    counts: { all: number; unread: number; mine: number };
    thread: StaffThread | null;
    notificationsPage: { data: NotificationItem[]; meta: SimpleMeta } | null;
    audiences: BroadcastAudienceOption[] | null;
    broadcasts: BroadcastItem[] | null;
    urls: { index: string; broadcast: string | null; readAll: string };
};
