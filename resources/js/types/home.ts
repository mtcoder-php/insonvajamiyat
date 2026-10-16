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
    doi: string | null;
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
    url: string;
    imageUrl: string | null;
};

export type PostDetail = PostItem & { body: string | null };

export type EventItem = {
    id: number;
    slug: string;
    title: string;
    location: string | null;
    startsAt: string;
    endsAt: string | null;
    registrationUrl: string | null;
    imageUrl: string | null;
    url: string;
};

export type EventDetail = EventItem & {
    description: string | null;
    isPast: boolean;
};

/** Bosh sahifa slayderi (banner yoki config'dagi standart slayd) */
/** Kutubxona kitobi kartochkasi (BookResource) — bosh sahifa va /library */
export type LibraryBook = {
    id: number;
    slug: string;
    title: string;
    author: string;
    year: number | null;
    pages: number | null;
    language: 'uz' | 'ru' | 'en';
    coverUrl: string | null;
    subject: { name: string; slug: string } | null;
    hasFile: boolean;
    isFeatured: boolean;
    pageUrl: string;
    url: string | null;
};

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
    news: PostItem[];
    events: EventItem[];
    books: LibraryBook[];
    partners: PartnerItem[];
};

/** Hamkor tashkilot yoki indekslash bazasi (bosh sahifa pastidagi qator) */
export type PartnerItem = {
    id: number;
    type: 'partner' | 'indexing';
    name: string;
    subtitle: string | null;
    url: string | null;
    logoUrl: string | null;
};
