import type { LibraryBook, Paginated } from '@/types';

/** Kutubxona tillari */
export type LibraryLanguage = 'uz' | 'ru' | 'en';

export type LibrarySort = 'recommended' | 'newest' | 'popular' | 'title';

/** Sayt: /library */
export type LibraryIndexProps = {
    hero: string | null;
    books: Paginated<LibraryBook>;
    featured: LibraryBook[];
    subjects: { value: string; label: string }[];
    filters: {
        q: string;
        subject: string | null;
        language: LibraryLanguage | null;
        sort: LibrarySort;
    };
    stats: { books: number; files: number; downloads: number };
};

/** Sayt: /library/{slug} */
export type LibraryBookDetail = LibraryBook & {
    description: string | null;
    publisher: string | null;
    isbn: string | null;
    views: number;
    downloads: number;
    file: { size: number | null; readUrl: string; downloadUrl: string } | null;
    externalUrl: string | null;
};

export type LibraryShowProps = {
    hero: string | null;
    book: LibraryBookDetail;
    related: LibraryBook[];
};

/** Admin → Kutubxona */
export type AdminBook = {
    id: number;
    slug: string;
    title: string;
    author: string;
    publisher: string | null;
    year: number | null;
    pages: number | null;
    isbn: string | null;
    language: LibraryLanguage;
    subjectId: number | null;
    subject: string | null;
    coverUrl: string | null;
    url: string | null;
    file: { name: string | null; size: number | null; url: string } | null;
    isFeatured: boolean;
    isActive: boolean;
    sortOrder: number;
    views: number;
    downloads: number;
    translations: {
        title: { uz: string; ru: string; en: string };
        description: { uz: string; ru: string; en: string };
    };
    urls: {
        update: string;
        toggle: string;
        destroy: string;
        public: string | null;
    };
};

export type AdminLibraryStatus =
    | 'all'
    | 'featured'
    | 'active'
    | 'hidden'
    | 'pdf';

export type AdminLibraryProps = {
    filters: { q: string; status: AdminLibraryStatus; subject: number | null };
    stats: {
        total: number;
        featured: number;
        files: number;
        downloads: number;
        views: number;
    };
    subjects: { value: number; label: string }[];
    books: {
        data: AdminBook[];
        meta: Paginated<AdminBook>['meta'];
    };
    storeUrl: string;
};
