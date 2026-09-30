/**
 * Bosh sahifa ma'lumotlari — App\Services\Web\HomePageService va
 * App\Http\Resources\Web\* bilan mos.
 */
export type ArticleCard = {
    id: number;
    slug: string;
    url: string;
    title: string;
    authors: string;
    subject: { name: string; slug: string } | null;
    coverUrl: string | null;
    doi: string | null;
    publishedAt: string | null;
    views: number;
    downloads: number;
};

export type IssueCard = {
    id: number;
    slug: string;
    url: string;
    label: string;
    number: number;
    year: number;
    volume: number | null;
    title: string | null;
    description: string | null;
    coverUrl: string | null;
    pdfUrl: string | null;
    pdfSize: number | null;
    tocUrl: string | null;
    publishedAt: string | null;
    articlesCount: number | null;
    pagesTotal: number | null;
};

export type LatestIssue = IssueCard & { subjects: string[] };

export type SubjectSummary = {
    id: number;
    slug: string;
    name: string;
    nameEn: string | null;
    articlesCount: number;
};

export type PostItem = {
    id: number;
    slug: string;
    type: 'news' | 'announcement';
    title: string;
    excerpt: string | null;
    isPinned: boolean;
    publishedAt: string | null;
};

export type EventItem = {
    id: number;
    slug: string;
    title: string;
    location: string | null;
    startsAt: string;
    endsAt: string | null;
    registrationUrl: string | null;
};

export type PartnerItem = {
    id: number;
    name: string;
    subtitle: string | null;
    logoUrl: string | null;
    url: string | null;
};

/** Bosh sahifa slayderi (banner yoki config'dagi standart slayd) */
export type HeroSlide = {
    key: string;
    title: string;
    subtitle: string | null;
    imageUrl: string | null;
    linkUrl: string | null;
    buttonText: string | null;
};

export type HomePageProps = {
    heroSlides: HeroSlide[];
    latestIssue: LatestIssue | null;
    latestArticles: ArticleCard[];
    subjects: SubjectSummary[];
    announcements: PostItem[];
    news: PostItem[];
    events: EventItem[];
    partners: PartnerItem[];
    indexing: PartnerItem[];
};
