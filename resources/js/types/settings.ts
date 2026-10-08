import type { SimpleMeta } from './reports';

/* ------------------------------------------------------------------
 * Admin → Sozlamalar — App\Services\Content\SettingsWorkspace
 * ------------------------------------------------------------------ */

export type SettingsTab =
    | 'subjects'
    | 'types'
    | 'pages'
    | 'documents'
    | 'board'
    | 'banners'
    | 'posts'
    | 'events'
    | 'books'
    | 'partners';

/** Tarjima qilinadigan maydon: o'zbekcha majburiy, rus/ingliz ixtiyoriy */
export type Translated = { uz: string; ru: string; en: string };

export type SettingsSubject = {
    id: number;
    name: string;
    translations: Translated;
    slug: string;
    code: string | null;
    parentId: number | null;
    isActive: boolean;
    sortOrder: number;
    articlesCount: number;
    urls: { update: string; destroy: string };
};

export type SettingsArticleType = {
    id: number;
    name: string;
    translations: { name: Translated; description: Translated };
    slug: string;
    price: number;
    currency: string;
    reviewDays: number | null;
    isActive: boolean;
    sortOrder: number;
    articlesCount: number;
    urls: { update: string; destroy: string };
};

export type SettingsBanner = {
    id: number;
    title: string;
    translations: {
        title: Translated;
        subtitle: Translated;
        button_text: Translated;
    };
    imageUrl: string | null;
    linkUrl: string | null;
    isActive: boolean;
    sortOrder: number;
    startsAt: string | null;
    endsAt: string | null;
    visible: boolean;
    urls: { update: string; destroy: string };
};

export type SettingsPaged<T, C> = {
    data: T[];
    meta: SimpleMeta;
    counts: C;
};

export type SettingsPostStatus = 'published' | 'scheduled' | 'draft';

export type SettingsPost = {
    id: number;
    type: 'news' | 'announcement';
    slug: string;
    title: string;
    translations: { title: Translated; excerpt: Translated; body: Translated };
    imageUrl: string | null;
    isPublished: boolean;
    isPinned: boolean;
    /** "2026-10-07T09:30" (datetime-local formati) */
    publishedAt: string | null;
    status: SettingsPostStatus;
    author: string | null;
    url: string | null;
    urls: { update: string; destroy: string };
};

export type SettingsEvent = {
    id: number;
    slug: string;
    title: string;
    translations: {
        title: Translated;
        description: Translated;
        location: Translated;
    };
    location: string | null;
    startsAt: string;
    endsAt: string | null;
    registrationUrl: string | null;
    imageUrl: string | null;
    isPublished: boolean;
    isPast: boolean;
    url: string | null;
    urls: { update: string; destroy: string };
};

export type SettingsBook = {
    id: number;
    title: string;
    translations: { title: Translated };
    author: string;
    year: number | null;
    url: string | null;
    coverUrl: string | null;
    isActive: boolean;
    sortOrder: number;
    urls: { update: string; destroy: string };
};

export type SettingsPartnerType = 'partner' | 'indexing';

export type SettingsPartner = {
    id: number;
    type: SettingsPartnerType;
    name: string;
    translations: { name: Translated; subtitle: Translated };
    url: string | null;
    logoUrl: string | null;
    isActive: boolean;
    sortOrder: number;
    urls: { update: string; destroy: string };
};

export type SettingsFilters = {
    type: '' | 'news' | 'announcement';
    when: '' | 'upcoming' | 'past';
    q: string;
};

export type SettingsPageProps = {
    tab: SettingsTab;
    tabs: SettingsTab[];
    filters: SettingsFilters;
    subjects: SettingsSubject[] | null;
    types: SettingsArticleType[] | null;
    banners: SettingsBanner[] | null;
    posts: SettingsPaged<
        SettingsPost,
        { all: number; news: number; announcement: number }
    > | null;
    events: SettingsPaged<
        SettingsEvent,
        { all: number; upcoming: number }
    > | null;
    books: SettingsBook[] | null;
    partners: SettingsPartner[] | null;
    partnerTypes: { value: SettingsPartnerType; label: string }[] | null;
    pages: SettingsPage[] | null;
    board: SettingsBoardMember[] | null;
    boardRoles: { value: SettingsBoardRole; label: string }[] | null;
    documents: SettingsDocument[] | null;
    documentKinds: { value: SettingsDocumentKind; label: string }[] | null;
    documentLimits: { extensions: string[]; maxKb: number } | null;
    urls: {
        index: string;
        subjects: string;
        types: string | null;
        banners: string;
        posts: string;
        events: string;
        books: string;
        partners: string;
        board: string;
        documents: string;
    };
};

/** Statik sahifa (admin) — App\\Services\\Content\\PageService::form */
export type SettingsPageSection = { heading: Translated; body: Translated };

export type SettingsPage = {
    slug: 'about' | 'guidelines' | 'contact';
    label: string;
    title: Translated;
    description: Translated;
    sections: SettingsPageSection[];
    /** false — standart matn ko'rsatilmoqda (hali tahrirlanmagan) */
    isCustom: boolean;
    updatedAt: string | null;
    updatedBy: string | null;
    publicUrl: string;
    urls: { update: string; reset: string };
};

export type SettingsBoardRole =
    | 'chief_editor'
    | 'deputy_chief_editor'
    | 'executive_secretary'
    | 'member';

/** Tahririyat kengashi a'zosi — App\\Services\\Content\\EditorialBoardService::admin */
export type SettingsBoardMember = {
    id: number;
    name: string;
    role: SettingsBoardRole;
    roleLabel: string;
    translations: {
        full_name: Translated;
        position: Translated;
        organization: Translated;
        academic_degree: Translated;
    };
    country: string | null;
    email: string | null;
    orcid: string | null;
    photoUrl: string | null;
    isActive: boolean;
    sortOrder: number;
    urls: { update: string; destroy: string };
};

export type SettingsDocumentKind = 'template' | 'guide' | 'form' | 'other';

/** Mualliflar uchun fayl — App\\Services\\Content\\JournalDocumentService::admin */
export type SettingsDocument = {
    id: number;
    kind: SettingsDocumentKind;
    kindLabel: string;
    name: string;
    translations: { title: Translated; description: Translated };
    originalName: string;
    extension: string;
    size: number;
    downloads: number;
    isActive: boolean;
    sortOrder: number;
    updatedAt: string | null;
    updatedBy: string | null;
    urls: { download: string; update: string; destroy: string };
};
