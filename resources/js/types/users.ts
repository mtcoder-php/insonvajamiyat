/**
 * Foydalanuvchilar — App\Http\Resources\Admin\UserListResource / UserDetailResource bilan mos.
 */
import type { RoleName } from '@/types/auth';

export type RoleOption = {
    value: RoleName;
    label: string;
    staff: boolean;
};

export type UserRole = {
    value: RoleName;
    label: string;
};

export type UserListItem = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    avatarUrl: string | null;
    organization: string | null;
    roles: UserRole[];
    isBlocked: boolean;
    isVerified: boolean;
    isDeleted: boolean;
    lastLoginAt: string | null;
    createdAt: string | null;
};

export type UserDetail = UserListItem & {
    lastName: string;
    firstName: string;
    middleName: string | null;
    fullName: string;
    locale: string;
    position: string | null;
    department: string | null;
    academicDegree: string | null;
    academicTitle: string | null;
    orcid: string | null;
    city: string | null;
    bio: string | null;
    blockedAt: string | null;
    blockedReason: string | null;
    lastLoginIp: string | null;
    emailVerifiedAt: string | null;
    twoFactorEnabled: boolean;
    isSelf: boolean;
    can: { update: boolean; delete: boolean; block: boolean };
};

export type UserFilters = {
    search: string | null;
    role: RoleName | 'staff' | null;
    status: 'active' | 'blocked' | 'unverified' | 'deleted' | null;
    sort: 'latest' | 'oldest' | 'name' | 'last_login';
};

export type UserCounts = {
    total: number;
    staff: number;
    authors: number;
    blocked: number;
    deleted: number;
};

export type UserActivity = {
    articles: number;
    publishedArticles: number;
    paidTotal: number;
    aiRequests: number;
};

export type UserArticleItem = {
    id: number;
    title: string;
    status: string;
    statusGroup: string;
    statusLabel: string;
    createdAt: string | null;
};

/** Laravel paginator (JsonResource::collection) */
export type Paginated<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
        links: { url: string | null; label: string; active: boolean }[];
    };
};
