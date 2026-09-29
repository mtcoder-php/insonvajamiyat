import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { RoleName } from '@/types';

/**
 * Frontend'da menyu/tugmalarni ko'rsatish uchun rol va ruxsat tekshiruvi.
 * Bu faqat UI qulayligi — haqiqiy himoya serverda (middleware, Policy).
 */
export function usePermissions() {
    const page = usePage();

    const auth = computed(() => page.props.auth);

    const isSuperAdmin = computed(() =>
        auth.value.roles.includes('super_admin'),
    );

    function can(permission: string): boolean {
        const permissions = auth.value.permissions;

        return permissions.includes('*') || permissions.includes(permission);
    }

    function canAny(permissions: string[]): boolean {
        return permissions.some((permission) => can(permission));
    }

    function hasRole(...roles: RoleName[]): boolean {
        return roles.some((role) => auth.value.roles.includes(role));
    }

    return {
        auth,
        isStaff: computed(() => auth.value.isStaff),
        isSuperAdmin,
        can,
        canAny,
        hasRole,
    };
}
