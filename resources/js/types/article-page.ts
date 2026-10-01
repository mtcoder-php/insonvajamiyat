/**
 * Nashr etilgan maqola sahifasi — App\Services\Web\ArticlePageService::show() bilan mos.
 */
import type { ArticleCard } from './home';

export type ArticlePageAuthor = {
    name: string;
    shortName: string;
    organization: string | null;
    affiliation: number | null;
    degree: string | null;
    orcid: string | null;
    isCorresponding: boolean;
};

export type ArticleNeighbour = { title: string; url: string } | null;

export type ArticlePage = {
    id: number;
    slug: string;
    title: string;
    abstract: string | null;
    keywords: string[];
    references: string | null;
    subject: { name: string; slug: string } | null;
    language: string;
    type: string;
    doi: string | null;
    doiUrl: string | null;
    udc: string | null;
    coverUrl: string | null;
    views: number;
    downloads: number;
    submittedAt: string | null;
    acceptedAt: string | null;
    publishedAt: string | null;
    pages: string | null;
    issue: {
        label: string;
        year: number;
        number: number;
        volume: number | null;
        url: string;
    } | null;
    authors: ArticlePageAuthor[];
    affiliations: string[];
    pdf: { viewUrl: string; downloadUrl: string; size: number | null } | null;
    citations: { apa: string; gost: string };
    neighbours: { prev: ArticleNeighbour; next: ArticleNeighbour };
    related: ArticleCard[];
};

export type ArticlePageProps = {
    article: ArticlePage;
    links: { guidelines: string; template: string | null; about: string };
};
