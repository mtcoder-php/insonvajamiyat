/** Statik sahifalar — App\Services\Content\PageService, EditorialBoardService */

export type StaticPageSection = { heading: string; body: string };

export type StaticPage = {
    title: string;
    description: string;
    sections: StaticPageSection[];
    updatedAt: string | null;
};

export type BoardMember = {
    id: number;
    name: string;
    position: string | null;
    organization: string | null;
    degree: string | null;
    country: string | null;
    orcid: string | null;
    photoUrl: string | null;
};

export type BoardGroup = {
    role: string;
    label: string;
    members: BoardMember[];
};

export type AboutPageProps = {
    page: StaticPage;
    /** Sarlavha rasmi (config journal.heroes) */
    hero: string | null;
    board: BoardGroup[];
    facts: { label: string; value: string }[];
    subjects: { name: string; slug: string }[];
    indexing: {
        id: number;
        name: string;
        url: string | null;
        logoUrl: string | null;
    }[];
    stats: {
        articles: number;
        issues: number;
        authors: number;
        subjects: number;
    };
};

/** Mualliflar uchun yuklab olinadigan fayl — JournalDocumentService::public */
export type PublicDocument = {
    id: number;
    kind: 'template' | 'guide' | 'form' | 'other';
    kindLabel: string;
    title: string;
    description: string | null;
    extension: string;
    size: number;
    url: string;
    updatedAt: string | null;
};

export type GuidelinesPageProps = {
    page: StaticPage;
    /** Sarlavha rasmi (config journal.heroes) */
    hero: string | null;
    template: string | null;
    documents: PublicDocument[];
    types: {
        id: number;
        name: string;
        description: string | null;
        price: number;
        currency: string;
        reviewDays: number | null;
    }[];
    plagiarismMax: number;
    submitUrl: string;
};

export type ContactPageProps = {
    page: StaticPage;
    /** Sarlavha rasmi (config journal.heroes) */
    hero: string | null;
};

/** Obuna holati sahifasi (web/newsletter/Status) */
export type NewsletterStatusProps = {
    state: 'confirmed' | 'ask' | 'unsubscribed' | 'invalid';
    /** Faqat 'ask' holatida — chiqish formasi uchun */
    token: string | null;
    /** Yashirilgan manzil: r•••r@example.com */
    email: string | null;
};
