import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

interface FlashProps {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
    message?: string;
}

interface PageProps extends Record<string, unknown> {
    auth: {
        user: any;
    };
    flash: FlashProps;
    errors: Record<string, string>;
}

export default function FlashNotifications() {
    const { flash, errors } = usePage<PageProps>().props;

    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success);
        }
        if (flash.error) {
            toast.error(flash.error);
        }
        if (flash.warning) {
            toast.warning(flash.warning);
        }
        if (flash.info || flash.message) {
            toast.info(flash.info || flash.message);
        }
    }, [flash]);

    useEffect(() => {
        const errorKeys = Object.keys(errors);
        if (errorKeys.length > 0) {
            if (errorKeys.length === 1) {
                toast.error(errors[errorKeys[0]]);
            } else {
                toast.error(`There were ${errorKeys.length} validation issues. Please check the form.`);
            }
        }
    }, [errors]);

    return null;
}
