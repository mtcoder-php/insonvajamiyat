/**
 * Jurnallar bo'limi — App\Services\Issues\IssueWorkspace bilan mos.
 */
import type { ArticleStatusGroup } from './cabinet';

export type IssueStatusKey = 'draft' | 'published';

export type AdminIssueCard = {
    slug: string;
    label: string;
    title: string | null;
    year: number;
    volume: number | null;
    number: number;
    doi: string | null;
    status: IssueStatusKey;
    statusLabel: string;
    publishedAt: string | null;
    coverUrl: string | null;
    articles: number;
    ready: number;
    pages: number;
    hasPdf: boolean;
    url: string;
};

export type IssueStat = {
    key: string;
    label: string;
    value: number;
    hint: string;
};

export type IssueFormData = {
    year: number | null;
    volume: number | null;
    number: number | null;
    doi: string | null;
    title: string | null;
    description: string | null;
};

export type IssueIndexProps = {
    filters: { year: number | null; status: IssueStatusKey | null };
    issues: AdminIssueCard[];
    stats: IssueStat[];
    years: number[];
    next: { year: number; number: number; volume: number | null };
    storeUrl: string;
};

export type IssueFileInfo = {
    url: string | null;
    name: string;
    size: number | null;
};

export type IssueArticleRow = {
    id: number;
    uuid: string;
    position: number;
    code: string;
    title: string;
    authors: string;
    subject: string | null;
    status: string;
    statusGroup: ArticleStatusGroup;
    statusLabel: string;
    section: string | null;
    pageFrom: number | null;
    pageTo: number | null;
    pagesCount: number | null;
    doi: string | null;
    approved: boolean;
    ready: boolean;
    hasFinalPdf: boolean;
    editable: boolean;
    productionUrl: string;
    urls: { update: string; destroy: string };
};

export type IssueDetail = {
    slug: string;
    label: string;
    year: number;
    volume: number | null;
    number: number;
    doi: string | null;
    title: string | null;
    description: string | null;
    status: IssueStatusKey;
    statusLabel: string;
    publishedAt: string | null;
    createdAt: string | null;
    coverUrl: string | null;
    hasOwnCover: boolean;
    files: { pdf: IssueFileInfo | null; toc: IssueFileInfo | null };
    pdfBuild: IssuePdfBuild;
    /** Crossref DOI deposit XML (faqat chop etilgan son) — App\\Services\\Indexing\\CrossrefDeposit */
    crossref: { count: number; total: number; url: string } | null;
    articles: IssueArticleRow[];
    /** Chop etishga to'sqinlik qilayotgan sabablar (qoralama son uchun) */
    problems: string[];
    publicUrl: string | null;
    summary: {
        total: number;
        ready: number;
        withPages: number;
        pages: number;
        complete: boolean;
    };
    can: { manage: boolean; delete: boolean; publish: boolean };
    urls: {
        index: string;
        update: string;
        destroy: string;
        files: string;
        fileDestroy: string;
        attach: string;
        reorder: string;
        paginate: string;
        toc: string;
        publish: string;
    };
};

export type AvailableArticle = {
    id: number;
    code: string;
    title: string;
    author: string;
    statusLabel: string;
    statusGroup: ArticleStatusGroup;
    approved: boolean;
    pagesCount: number | null;
};

export type IssueShowProps = {
    issue: IssueDetail;
    available: AvailableArticle[];
};

/** Butun son PDF ni avtomatik yig'ish — App\\Services\\Issues\\IssueWorkspace::pdfBuild */
export type IssuePdfBuild = {
    status: 'queued' | 'processing' | 'done' | 'failed' | null;
    error: string | null;
    pages: number | null;
    auto: boolean;
    builtAt: string | null;
    busy: boolean;
    stale: boolean;
    checks: {
        key: string;
        label: string;
        ok: boolean;
        required: boolean;
        detail: string | null;
    }[];
    canBuild: boolean;
    buildUrl: string;
};
