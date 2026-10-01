/**
 * Muharrir ish joyi — App\Services\Editorial\EditorialWorkspace bilan mos.
 */
import type { ArticleStatusGroup, TimelineStep } from './cabinet';
import type { LocaleCode } from './journal';
import type { ThreadMessage } from './messages';
import type { EditorialReview, ReviewerOption } from './reviews';

export type EditorialQueue =
    | 'new'
    | 'reviewing'
    | 'revision'
    | 'accepted'
    | 'payment'
    | 'published'
    | 'closed'
    | 'mine'
    | 'all';

export type EditorialDecisionKey = 'request_revision' | 'accept' | 'reject';

export type EditorialListItem = {
    uuid: string;
    code: string;
    title: string;
    author: string;
    subject: string | null;
    status: string;
    statusGroup: ArticleStatusGroup;
    statusLabel: string;
    editor: string | null;
    date: string | null;
    updatedAt: string | null;
};

export type EditorialFile = {
    uuid: string;
    name: string;
    typeLabel: string;
    extension: string;
    size: number;
    uploadedAt: string | null;
    url: string;
};

export type EditorialArticle = {
    uuid: string;
    code: string;
    title: string;
    status: string;
    statusGroup: ArticleStatusGroup;
    statusLabel: string;
    type: string;
    subject: string | null;
    language: LocaleCode;
    udc: string | null;
    issue: string | null;
    submittedAt: string | null;
    paymentStatusLabel: string;
    reviewRound: number;
    submitter: { name: string; email: string; organization: string | null };
    handlingEditor: { id: number; name: string } | null;
    abstracts: Record<LocaleCode, string | null>;
    titles: Record<LocaleCode, string | null>;
    keywords: Record<LocaleCode, string[]>;
    references: string | null;
    authors: {
        name: string;
        organization: string | null;
        email: string | null;
        orcid: string | null;
        degree: string | null;
        isCorresponding: boolean;
    }[];
    files: EditorialFile[];
    versions: {
        number: number;
        type: string;
        round: number;
        note: string | null;
        createdAt: string | null;
        files: EditorialFile[];
    }[];
    history: {
        id: number;
        statusGroup: ArticleStatusGroup;
        statusLabel: string;
        comment: string | null;
        visibleToAuthor: boolean;
        actor: string | null;
        createdAt: string;
    }[];
    decisions: {
        id: number;
        decision: EditorialDecisionKey | 'send_to_review';
        label: string;
        round: number;
        commentToAuthor: string | null;
        internalNote: string | null;
        editor: string;
        createdAt: string;
    }[];
    reviews: EditorialReview[];
    messages: ThreadMessage[];
    notes: {
        id: number;
        body: string;
        author: string;
        avatar: string | null;
        role: string | null;
        mine: boolean;
        createdAt: string;
    }[];
    steps: TimelineStep[];
    can: {
        startReview: boolean;
        decide: boolean;
        assign: boolean;
        invite: boolean;
        message: boolean;
        note: boolean;
    };
    availableDecisions: EditorialDecisionKey[];
    urls: {
        startReview: string;
        decision: string;
        editor: string;
        notes: string;
        invite: string;
        message: string;
    };
};

export type EditorialStat = {
    key: 'all' | 'new' | 'reviewing' | 'revision' | 'accepted';
    value: number;
    month: number;
};

export type EditorialPageProps = {
    filters: { queue: EditorialQueue; search: string | null };
    counts: Record<EditorialQueue, number>;
    stats: EditorialStat[];
    articles: {
        data: EditorialListItem[];
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            from: number | null;
            to: number | null;
            links: { url: string | null; label: string; active: boolean }[];
        };
    };
    selected: EditorialArticle | null;
    editors: { id: number; name: string }[];
    reviewers: ReviewerOption[];
};
