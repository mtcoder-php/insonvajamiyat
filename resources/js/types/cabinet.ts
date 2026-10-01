/**
 * Muallif kabineti ma'lumotlari — App\Services\Cabinet\AuthorDashboardService,
 * App\Http\Resources\Cabinet\AuthorArticleResource va
 * App\Http\Controllers\Cabinet\ArticleController bilan mos.
 */

/** ArticleStatus::group() */
export type ArticleStatusGroup =
    | 'draft'
    | 'new'
    | 'reviewing'
    | 'revision'
    | 'accepted'
    | 'published'
    | 'rejected'
    | 'withdrawn';

export type AuthorArticle = {
    id: number;
    uuid: string;
    title: string;
    subject: string | null;
    keywords: string[];
    issue: string | null;
    status: string;
    statusGroup: ArticleStatusGroup;
    statusLabel: string;
    submittedAt: string | null;
    updatedAt: string | null;
    url: string;
    publicUrl: string | null;
};

export type AuthorStatCard = {
    key: 'total' | 'reviewing' | 'revision' | 'accepted' | 'published';
    value: number;
    delta: number | null;
    hint: string;
};

export type TimelineStepState =
    | 'done'
    | 'current'
    | 'pending'
    | 'skipped'
    | 'failed';

export type TimelineStep = {
    key: string;
    label: string;
    state: TimelineStepState;
    date: string | null;
};

export type AuthorMessage = {
    id: number;
    sender: string;
    message: string;
    title: string;
    url: string;
    createdAt: string;
};

export type AuthorChart = {
    /** 'YYYY-MM' × 6 */
    months: string[];
    submitted: number[];
    inProgress: number[];
    published: number[];
};

export type CabinetLinks = {
    template: string | null;
    guidelines: string;
};

export type AuthorDashboardProps = {
    profileCompleted: boolean;
    cards: AuthorStatCard[];
    articles: AuthorArticle[];
    focus: { article: AuthorArticle; steps: TimelineStep[] } | null;
    messages: AuthorMessage[];
    chart: AuthorChart;
    links: CabinetLinks;
};

export type ArticleFilterKey =
    | 'reviewing'
    | 'revision'
    | 'accepted'
    | 'published'
    | 'draft'
    | 'closed';

export type AuthorArticleDetails = AuthorArticle & {
    abstract: string | null;
    keywords: string[];
    type: string;
    language: string | null;
    udc: string | null;
    doi: string | null;
    paymentStatus: string;
    paymentStatusLabel: string;
    reviewRound: number;
    acceptedAt: string | null;
    publishedAt: string | null;
    authors: {
        id: number;
        name: string;
        organization: string | null;
        email: string | null;
        orcid: string | null;
        isCorresponding: boolean;
    }[];
    files: {
        id: number;
        name: string;
        type: string;
        typeLabel: string;
        extension: string;
        size: number;
        uploadedAt: string | null;
        url: string;
    }[];
    history: {
        id: number;
        status: string;
        statusGroup: ArticleStatusGroup;
        statusLabel: string;
        comment: string | null;
        createdAt: string;
    }[];
    can: { withdraw: boolean; update: boolean };
};
