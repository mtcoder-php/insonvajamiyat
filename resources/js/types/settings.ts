import type { SimpleMeta } from './reports';

/* ------------------------------------------------------------------
 * Admin → Sozlamalar — App\Services\Content\SettingsWorkspace
 * ------------------------------------------------------------------ */

export type SettingsTab =
    | 'subjects'
    | 'types'
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
    urls: {
        index: string;
        subjects: string;
        types: string | null;
        banners: string;
        posts: string;
        events: string;
        books: string;
        partners: string;
    };
};
