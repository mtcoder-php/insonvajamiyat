/**
 * Nashr jarayoni — App\Services\Production\ProductionWorkspace bilan mos.
 */
import type { ArticleStatusGroup } from './cabinet';
import type { EditorialNote } from './editorial';
import type { PaginationMeta } from './reviews';

export type ProductionTab =
    | 'new'
    | 'production'
    | 'approved'
    | 'published'
    | 'all';

export type ProductionListItem = {
    uuid: string;
    code: string;
    title: string;
    author: string;
    subject: string | null;
    status: string;
    statusGroup: ArticleStatusGroup;
    statusLabel: string;
    issue: string | null;
    pages: string | null;
    doi: string | null;
    approved: boolean;
    progress: { done: number; total: number };
    acceptedAt: string | null;
    url: string;
};

export type ProductionStat = {
    key: string;
    label: string;
    value: number;
    hint: string;
};

export type ProductionIndexProps = {
    filters: { tab: ProductionTab; search: string | null };
    counts: Record<ProductionTab, number>;
    stats: ProductionStat[];
    articles: { data: ProductionListItem[]; meta: PaginationMeta };
};

export type ProductionCheck = {
    key: string;
    label: string;
    ok: boolean;
    hint: string | null;
};

export type ProductionStep = {
    key: string;
    label: string;
    state: 'done' | 'current' | 'todo';
    date: string | null;
};

export type ProductionFile = {
    uuid: string;
    name: string;
    type: string;
    typeLabel: string;
    extension: string;
    size: number;
    uploadedAt: string | null;
    viewUrl: string | null;
    downloadUrl: string;
};

export type ProductionMetadataForm = {
    doi: string | null;
    udc: string | null;
    plagiarism_percent: number | null;
    issue_id: number | null;
    page_from: number | null;
    page_to: number | null;
};

export type ProductionArticle = {
    uuid: string;
    code: string;
    title: string;
    status: string;
    statusGroup: ArticleStatusGroup;
    statusLabel: string;
    type: string;
    subject: string | null;
    language: string;
    abstract: string | null;
    keywords: string[];
    doi: string | null;
    udc: string | null;
    pagesCount: number | null;
    plagiarism: number | null;
    plagiarismMax: number;
    acceptedAt: string | null;
    submitter: string;
    authors: {
        name: string;
        organization: string | null;
        isCorresponding: boolean;
    }[];
    issue: {
        id: number;
        label: string;
        year: number;
        volume: number | null;
        number: number;
        status: string;
        statusLabel: string;
        publishedAt: string | null;
        articlesCount: number;
        pageFrom: number | null;
        pageTo: number | null;
        position: number;
    } | null;
    files: ProductionFile[];
    coverUrl: string | null;
    /** Serverning fayl yuklash chegarasi (bayt), 0 — cheklanmagan */
    uploadLimit: number;
    finalPdf: { name: string; size: number; uploadedAt: string | null } | null;
    preview: {
        name: string;
        isFinal: boolean;
        url: string;
        downloadUrl: string;
    } | null;
    checks: ProductionCheck[];
    ready: boolean;
    steps: ProductionStep[];
    production: {
        formatOk: boolean;
        authorApproved: boolean;
        authorApprovedAt: string | null;
        proof: ProofState;
        authorChanges: string | null;
        authorChangesAt: string | null;
        layoutEditor: string | null;
        approvedBy: string | null;
        approvedAt: string | null;
    };
    notes: EditorialNote[];
    form: ProductionMetadataForm;
    can: {
        start: boolean;
        manage: boolean;
        edit: boolean;
        approve: boolean;
        revoke: boolean;
        waiveProof: boolean;
        cancel: boolean;
        cover: boolean;
        publish: boolean;
        note: boolean;
    };
    urls: {
        index: string;
        start: string;
        upload: string;
        metadata: string;
        format: string;
        approve: string;
        revoke: string;
        waiveProof: string;
        cancel: string;
        cover: string;
        notes: string;
        publish: string;
        public: string | null;
    };
};

export type IssueOption = {
    id: number;
    label: string;
    status: string;
    statusLabel: string;
};

export type ProductionShowProps = {
    article: ProductionArticle;
    issues: IssueOption[];
};

/** Korrektura holati (ProductionService::proofState) */
export type ProofStateKey =
    | 'none'
    | 'pending'
    | 'overdue'
    | 'changes'
    | 'approved'
    | 'waived';

export type ProofState = {
    state: ProofStateKey;
    dueAt: string | null;
    overdue: boolean;
    waived: {
        at: string | null;
        by: string | null;
        reason: string | null;
    } | null;
};

/** Muallif kabineti: nashrga tayyorlash bosqichi */
export type AuthorProduction = {
    proof: {
        name: string;
        size: number;
        uploadedAt: string | null;
        viewUrl: string;
        downloadUrl: string;
    } | null;
    approved: boolean;
    approvedAt: string | null;
    state: ProofStateKey;
    dueAt: string | null;
    waivedAt: string | null;
    deadlineDays: number;
    changes: string | null;
    readyForPublication: boolean;
    publishedAt: string | null;
    publicUrl: string | null;
    issue: string | null;
    pages: string | null;
    doi: string | null;
    canRespond: boolean;
    approveUrl: string;
    changesUrl: string;
};
