import { useEffect, useRef, useState } from 'react';

type Saver<T> = (payload: T) => Promise<void> | void;

export function useAutoSave<T>(saveFn: Saver<T>, delayMs = 600) {
    const [saving, setSaving] = useState(false);
    const timer = useRef<NodeJS.Timeout | null>(null);
    const latest = useRef<T | null>(null);

    useEffect(() => () => timer.current && clearTimeout(timer.current), []);

    const schedule = (payload: T) => {
        latest.current = payload;
        if (timer.current) {
            clearTimeout(timer.current);
        }
        timer.current = setTimeout(async () => {
            if (latest.current === null) return;
            setSaving(true);
            await saveFn(latest.current);
            setSaving(false);
        }, delayMs);
    };

    return { schedule, saving };
}
