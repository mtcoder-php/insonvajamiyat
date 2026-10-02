/**
 * Yozishma va tuzatish sikli — App\Services\Messages\ArticleMessageService::thread()
 * va App\Services\Articles\RevisionService::request() bilan mos.
 */
export type ThreadMessage = {
    id: number;
    body: string;
    side: 'author' | 'editorial';
    mine: boolean;
    sender: string;
    attachmentName: string | null;
    attachmentUrl: string | null;
    readAt: string | null;
    createdAt: string;
};

export type ArticleThread = {
    items: ThreadMessage[];
    sendUrl: string | null;
};

export type RevisionRequest = {
    comment: string | null;
    requestedAt: string | null;
    round: number;
    url: string;
};

/** Kabinet → Xabarlar: maqola bo'yicha yozishma (App\Services\Cabinet\AuthorInbox) */
export type InboxConversation = {
    uuid: string;
    code: string;
    title: string;
    status: string;
    statusLabel: string;
    statusGroup: string;
    unread: number;
    last: {
        body: string;
        mine: boolean;
        fromEditorial: boolean;
        createdAt: string;
    } | null;
    submittedAt: string | null;
    url: string;
    articleUrl: string;
};

export type InboxThread = {
    uuid: string;
    code: string;
    title: string;
    statusLabel: string;
    articleUrl: string;
    items: ThreadMessage[];
    sendUrl: string | null;
};
