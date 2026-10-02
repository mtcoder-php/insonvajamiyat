/**
 * Maqolalar katalogi va jurnal sonlari arxivi — App\Services\Web\{CatalogService, IssueArchiveService}.
 */
import type { ArticleCard, IssueCard } from './home';
import type { PaginationMeta } from './reviews';

export type CatalogSort =
    | 'newest'
    | 'oldest'
    | 'popular'
    | 'downloads'
    | 'title';

export type CatalogFilters = {
    q: string | null;
    subjects: string[];
    year: number | null;
    issue: string | null;
    author: string | null;
    keyword: string | null;
    sort: CatalogSort;
};

export type CatalogArticle = {
    id: number;
    url: string;
    title: string;
    authors: string;
    organization: string | null;
    subject: { name: string; slug: string } | null;
    keywords: string[];
    coverUrl: string | null;
    issue: { label: string; url: string } | null;
    doi: string | null;
    publishedAt: string | null;
    views: number;
    downloads: number;
    pdf: { size: number | null; viewUrl: string; downloadUrl: string } | null;
};

export type SubjectFacet = { slug: string; name: string; count: number };

export type CatalogProps = {
    filters: CatalogFilters;
    articles: { data: CatalogArticle[]; meta: PaginationMeta };
    facets: {
        subjects: SubjectFacet[];
        years: number[];
        issues: { slug: string; label: string }[];
    };
    sidebar: {
        keywords: string[];
        stats: {
            articles: number;
            issues: number;
            authors: number;
            subjects: number;
        };
        latestIssue: IssueCard | null;
        announcements: { title: string; url: string; date: string | null }[];
    };
    hero: string | null;
};

export type ArchiveIssue = IssueCard & { authorsCount: number };

export type IssueTreeYear = {
    year: number;
    count: number;
    issues: {
        slug: string;
        label: string;
        url: string;
        publishedAt: string | null;
    }[];
};

export type IssueArchiveProps = {
    filters: { year: number; sort: 'newest' | 'oldest' };
    tree: IssueTreeYear[];
    latest: ArchiveIssue[];
    yearIssues: ArchiveIssue[];
    subjects: SubjectFacet[];
    latestArticles: ArticleCard[];
    hero: string | null;
};

export type IssueTocArticle = {
    id: number;
    title: string;
    url: string;
    authors: string;
    subject: { name: string; slug: string } | null;
    pages: string | null;
    doi: string | null;
    views: number;
    pdfUrl: string | null;
};

export type IssuePageProps = {
    issue: ArchiveIssue;
    sections: { title: string | null; articles: IssueTocArticle[] }[];
    neighbours: {
        prev: { label: string; url: string } | null;
        next: { label: string; url: string } | null;
    };
};
