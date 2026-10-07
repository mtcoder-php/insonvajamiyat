/* ------------------------------------------------------------------
 * Admin → Mualliflar va Taqrizchilar — App\Services\People\*
 * ------------------------------------------------------------------ */

import type { ArticleStatusGroup } from './cabinet';
import type { SimpleMeta } from './reports';

export type PeopleOption = { value: number; label: string };

export type PeopleList<T> = { data: T[]; meta: SimpleMeta };

export type PersonProfile = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    avatarUrl: string | null;
    organization: string | null;
    department: string | null;
    position: string | null;
    degree: string | null;
    title: string | null;
    orcid: string | null;
    country: string | null;
    city: string | null;
    bio: string | null;
    isBlocked: boolean;
    isVerified: boolean;
    createdAt: string | null;
    lastLoginAt: string | null;
};

export type PersonSubject = { id: number; name: string };

export type PersonArticle = {
    uuid: string;
    code: string;
    title: string;
    status: string;
    statusLabel: string;
    statusGroup: ArticleStatusGroup;
    subject: string | null;
    submittedAt: string | null;
    publishedAt: string | null;
    views: number;
    downloads: number;
    adminUrl: string;
    publicUrl: string | null;
    isSubmitter: boolean;
};

/* ---------- Mualliflar ---------- */

export type AuthorFilters = {
    search: string;
    subject: number | null;
    sort: 'latest' | 'name' | 'articles' | 'published';
};

export type AuthorListItem = {
    id: number;
    name: string;
    email: string;
    avatarUrl: string | null;
    organization: string | null;
    degree: string | null;
    orcid: string | null;
    subjects: string[];
    articles: number;
    published: number;
    paid: number | null;
    isBlocked: boolean;
    createdAt: string | null;
    url: string;
};

export type AuthorCounts = {
    total: number;
    active: number;
    published: number;
    newThisMonth: number;
    orcid: number;
};

export type AuthorsPageProps = {
    filters: AuthorFilters;
    authors: PeopleList<AuthorListItem>;
    counts: AuthorCounts;
    subjects: PeopleOption[];
    canPayments: boolean;
    urls: { index: string };
};

export type AuthorPayment = {
    uuid: string;
    amount: number;
    currency: string;
    status: string;
    statusLabel: string;
    purpose: string;
    provider: string;
    article: string | null;
    receipt: string | null;
    date: string | null;
};

export type AuthorShowProps = {
    profile: PersonProfile;
    subjects: PersonSubject[];
    stats: {
        articles: number;
        submitted: number;
        coauthored: number;
        groups: Record<
            | 'new'
            | 'reviewing'
            | 'revision'
            | 'accepted'
            | 'published'
            | 'closed',
            number
        >;
        views: number;
        downloads: number;
        drafts: number;
        paid: number | null;
    };
    articles: PersonArticle[];
    payments: AuthorPayment[] | null;
    canPayments: boolean;
    urls: { index: string; user: string | null; reviewer: string | null };
};

/* ---------- Taqrizchilar ---------- */

export type ReviewerFilters = {
    search: string;
    subject: number | null;
    status: '' | 'available' | 'busy' | 'overdue' | 'paused';
    sort: 'name' | 'load' | 'completed' | 'latest';
};

export type ReviewerListItem = {
    id: number;
    name: string;
    email: string;
    avatarUrl: string | null;
    organization: string | null;
    degree: string | null;
    subjects: string[];
    active: number;
    completed: number;
    declined: number;
    overdue: number;
    avgDays: number | null;
    onTime: number | null;
    isPaused: boolean;
    isBlocked: boolean;
    url: string;
};

export type ReviewerCounts = {
    total: number;
    available: number;
    paused: number;
    active: number;
    overdue: number;
    avgDays: number | null;
};

export type ReviewerCandidate = {
    id: number;
    name: string;
    email: string;
    organization: string | null;
    avatarUrl: string | null;
};

export type ReviewersPageProps = {
    filters: ReviewerFilters;
    reviewers: PeopleList<ReviewerListItem>;
    counts: ReviewerCounts;
    subjects: PeopleOption[];
    busyFrom: number;
    urls: {
        index: string;
        store: string;
        candidates: string;
        createUser: string | null;
    };
};

export type ReviewerHistoryItem = {
    id: number;
    round: number;
    status: 'invited' | 'accepted' | 'declined' | 'completed' | 'cancelled';
    statusLabel: string;
    recommendation: string | null;
    recommendationLabel: string | null;
    score: number | null;
    invitedAt: string | null;
    dueAt: string | null;
    completedAt: string | null;
    isOverdue: boolean;
    article: {
        code: string;
        title: string;
        subject: string | null;
        url: string | null;
    };
};

export type ReviewerShowProps = {
    profile: PersonProfile;
    subjects: PersonSubject[];
    isReviewer: boolean;
    isPaused: boolean;
    pausedAt: string | null;
    stats: {
        invited: number;
        active: number;
        completed: number;
        declined: number;
        overdue: number;
        avgDays: number | null;
        onTime: number | null;
        acceptRate: number | null;
        avgScore: number | null;
        recommendations: Record<string, number>;
    };
    reviews: ReviewerHistoryItem[];
    subjectOptions: PeopleOption[];
    busyFrom: number;
    urls: {
        index: string;
        status: string;
        subjects: string;
        destroy: string;
        store: string;
        user: string | null;
        author: string | null;
    };
};
