import { usePage } from '@inertiajs/react';
import { User } from '@/types';

export function useAuth() {
    const { auth } = usePage().props as { auth: { user: User | null } };

    return {
        user: auth.user,
        isAuthenticated: auth.user !== null,
        isSuperAdmin: auth.user?.is_super_admin ?? false,
    };
}
