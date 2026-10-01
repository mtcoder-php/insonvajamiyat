import type { LocaleCode } from './journal';

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
    /** Qoralama — formani davom ettirish */
    editUrl: string | null;
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
    /** 'm12' — yozishma xabari, 'h34' — holat izohi */
    id: string;
    sender: string;
    message: string;
    title: string;
    url: string;
    createdAt: string;
    unread: boolean;
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
    can: { withdraw: boolean; update: boolean; edit: boolean; delete: boolean };
    editUrl: string | null;
    destroyUrl: string | null;
};

/** Maqola sahifasidagi nashr to'lovi bloki (Cabinet\ArticleController::payment) */
export type AuthorArticlePayment = {
    awaiting: boolean;
    amount: number;
    currency: string;
    status: string;
    statusLabel: string;
    receipt: string | null;
    provider: string | null;
    paidAt: string | null;
    requisites: Partial<
        Record<'recipient' | 'bank' | 'account' | 'mfo' | 'inn', string>
    >;
    purpose: string | null;
};

/* ------------------------------------------------------------------
 * Yangi maqola yuborish formasi — App\Services\Cabinet\SubmissionWizardData
 * ------------------------------------------------------------------ */

export type Localized<T> = Record<LocaleCode, T>;

export type WizardStepInfo = { number: number; key: string; label: string };

export type DraftAuthor = {
    last_name: string;
    first_name: string;
    middle_name: string | null;
    email: string | null;
    organization: string | null;
    position: string | null;
    academic_degree: string | null;
    orcid: string | null;
    is_me: boolean;
    is_corresponding?: boolean;
};

export type DraftFile = {
    uuid: string;
    name: string;
    type: string;
    typeLabel: string;
    extension: string;
    size: number;
    uploadedAt: string | null;
    url: string;
    deleteUrl: string;
};

export type ArticleDraft = {
    uuid: string;
    status: string;
    articleTypeId: number;
    subjectId: number | null;
    language: LocaleCode;
    udc: string | null;
    title: Localized<string | null>;
    abstract: Localized<string | null>;
    keywords: Localized<string[]>;
    references: string | null;
    authors: DraftAuthor[];
    files: DraftFile[];
    updatedAt: string | null;
    urls: {
        edit: string;
        show: string;
        details: string;
        authors: string;
        abstract: string;
        keywords: string;
        files: string;
        submit: string;
        destroy: string;
    };
};

export type ArticleTypeOption = {
    id: number;
    name: string;
    description: string | null;
    price: number;
    currency: string;
    reviewDays: number | null;
};

export type FileLimit = { label: string; extensions: string[]; maxKb: number };

export type WizardLimits = {
    maxAuthors: number;
    titleMin: number;
    titleMax: number;
    abstractMin: number;
    abstractMax: number;
    referencesMax: number;
    keywordsMin: number;
    keywordsMax: number;
    keywordMaxLength: number;
    supplementaryMax: number;
    files: Record<'manuscript' | 'supplementary', FileLimit>;
};

export type WizardOptions = {
    types: ArticleTypeOption[];
    subjects: { id: number; name: string }[];
    languages: { code: LocaleCode; label: string }[];
};

export type WizardProps = {
    step: number;
    steps: WizardStepInfo[];
    article: ArticleDraft | null;
    /** Bosqich raqami → kamchiliklar (bo'sh — to'liq) */
    checklist: Record<string, string[]> | null;
    isComplete: boolean;
    options: WizardOptions;
    me: Omit<DraftAuthor, 'is_me' | 'is_corresponding'>;
    limits: WizardLimits;
    consents: string[];
    links: CabinetLinks;
};
