import { useEffect, useRef, useState } from 'react';

type Saver<T> = (payload: T) => Promise<void> | void;

export function useAutoSave<T>(saveFn: Saver<T>, delayMs = 600) {
    const [saving, setSaving] = useState(false);
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const latest = useRef<T | null>(null);

    useEffect(() => {
        return () => {
            if (timer.current !== null) {
                clearTimeout(timer.current);
            }
        };
    }, []);

    const flush = async () => {
        if (timer.current !== null) {
            clearTimeout(timer.current);
            timer.current = null;
        }

        if (latest.current === null) {
            return;
        }

        const payload = latest.current;
        latest.current = null;

        setSaving(true);

        try {
            await saveFn(payload);
        } finally {
            setSaving(false);
        }
    };

    const schedule = (payload: T) => {
        latest.current = payload;

        if (timer.current !== null) {
            clearTimeout(timer.current);
        }

        timer.current = setTimeout(() => {
            void flush();
        }, delayMs);
    };

    const cancel = () => {
        if (timer.current !== null) {
            clearTimeout(timer.current);
            timer.current = null;
        }

        latest.current = null;
    };

    return { schedule, saving, flush, cancel };
}
