import { User } from './index';

declare module '@inertiajs/core' {
    interface PageProps {
        auth: {
            user: User | null;
        };
        flash: {
            success?: string;
            error?: string;
        };
    }
}
