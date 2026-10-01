/**
 * Taqrizlar — App\Services\Reviews\ReviewerWorkspace va EditorialWorkspace::detail() bilan mos.
 */
import type { LocaleCode } from './journal';

export type ReviewTab = 'invited' | 'active' | 'completed' | 'closed' | 'all';

export type ReviewStatusKey =
    | 'invited'
    | 'accepted'
    | 'declined'
    | 'completed'
    | 'cancelled';

export type ReviewListItem = {
    id: number;
    code: string;
    title: string;
    subject: string | null;
    type: string;
    round: number;
    status: ReviewStatusKey;
    statusLabel: string;
    recommendation: string | null;
    invitedAt: string | null;
    dueAt: string | null;
    daysLeft: number | null;
    url: string;
};

export type PaginationMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

export type ReviewIndexProps = {
    filters: { tab: ReviewTab };
    counts: Record<ReviewTab, number>;
    reviews: { data: ReviewListItem[]; meta: PaginationMeta };
};

export type ReviewFile = {
    uuid: string;
    name: string;
    typeLabel: string;
    extension: string;
    size: number;
    viewUrl: string;
    downloadUrl: string;
};

export type ReviewDetail = {
    id: number;
    status: ReviewStatusKey;
    statusLabel: string;
    round: number;
    dueAt: string | null;
    daysLeft: number | null;
    invitedAt: string | null;
    respondedAt: string | null;
    completedAt: string | null;
    article: {
        code: string;
        title: string;
        subject: string | null;
        type: string;
        language: LocaleCode;
        submittedAt: string | null;
        abstracts: Record<LocaleCode, string | null>;
        titles: Record<LocaleCode, string | null>;
        keywords: string[];
        authorResponse: {
            version: number;
            note: string | null;
            createdAt: string | null;
        } | null;
        files: ReviewFile[];
    };
    form: {
        score: number | null;
        criteria: Record<string, number>;
        recommendation: string | null;
        comments_to_author: string | null;
        comments_to_editor: string | null;
        attachmentUrl: string | null;
    };
    decisionMade: boolean;
    can: { respond: boolean; edit: boolean };
    urls: { accept: string; decline: string; save: string; index: string };
};

export type ReviewOptions = {
    criteria: { key: string; label: string }[];
    recommendations: { value: string; label: string }[];
};

export type ReviewShowProps = { review: ReviewDetail; options: ReviewOptions };

/** Muharrir kartasidagi taqriz */
export type EditorialReview = {
    id: number;
    reviewer: string;
    reviewerId: number;
    round: number;
    status: ReviewStatusKey;
    statusLabel: string;
    invitedAt: string | null;
    dueAt: string | null;
    daysLeft: number | null;
    completedAt: string | null;
    recommendation: string | null;
    recommendationLabel: string | null;
    score: number | null;
    criteria: { label: string; value: number | null }[];
    commentsToAuthor: string | null;
    commentsToEditor: string | null;
    attachmentUrl: string | null;
    cancelUrl: string | null;
};

export type ReviewerOption = {
    id: number;
    name: string;
    organization: string | null;
    active: number;
    completed: number;
};

/** Muallifga ko'rinadigan anonim taqriz */
export type AuthorReview = {
    id: number;
    label: string;
    round: number;
    recommendation: string | null;
    score: number | null;
    criteria: { label: string; value: number | null }[];
    comments: string | null;
    completedAt: string | null;
};
