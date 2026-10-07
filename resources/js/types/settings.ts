/* ------------------------------------------------------------------
 * Admin → Sozlamalar — App\Services\Content\SettingsWorkspace
 * ------------------------------------------------------------------ */

export type SettingsTab = 'subjects' | 'types' | 'banners';

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

export type SettingsPageProps = {
    tab: SettingsTab;
    tabs: SettingsTab[];
    subjects: SettingsSubject[] | null;
    types: SettingsArticleType[] | null;
    banners: SettingsBanner[] | null;
    urls: {
        index: string;
        subjects: string;
        types: string | null;
        banners: string;
    };
};
