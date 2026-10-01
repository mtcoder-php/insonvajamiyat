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
