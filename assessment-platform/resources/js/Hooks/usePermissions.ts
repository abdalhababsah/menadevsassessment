import { useAuth } from './useAuth';

export function usePermissions() {
    const { user, isSuperAdmin } = useAuth();

    const hasPermission = (permission: string): boolean => {
        if (!user) return false;
        if (isSuperAdmin) return true;
        return user.permissions.includes(permission);
    };

    const hasAnyPermission = (permissions: string[]): boolean => {
        if (!user) return false;
        if (isSuperAdmin) return true;
        return permissions.some((p) => user.permissions.includes(p));
    };

    const hasAllPermissions = (permissions: string[]): boolean => {
        if (!user) return false;
        if (isSuperAdmin) return true;
        return permissions.every((p) => user.permissions.includes(p));
    };

    const hasRole = (role: string): boolean => {
        if (!user) return false;
        return user.roles.includes(role);
    };

    return {
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,
        hasRole,
    };
}
