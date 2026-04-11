import axios from 'axios';
import { useEffect, useRef } from 'react';
import { RlhfState } from '@/types/rlhf';

type Props = {
    enabled: boolean;
    onUpdate: (state: RlhfState) => void;
};

export function useGenerationPolling({ enabled, onUpdate }: Props) {
    const onUpdateRef = useRef(onUpdate);

    useEffect(() => {
        onUpdateRef.current = onUpdate;
    }, [onUpdate]);

    useEffect(() => {
        if (!enabled) {
            return;
        }

        let cancelled = false;
        const interval = window.setInterval(async () => {
            try {
                const { data } = await axios.get('/quiz/rlhf/generation-status');

                if (cancelled) {
                    return;
                }

                onUpdateRef.current(data.state);

                if (data.responses_ready) {
                    window.clearInterval(interval);
                }
            } catch {
                window.clearInterval(interval);
            }
        }, 1500);

        return () => {
            cancelled = true;
            window.clearInterval(interval);
        };
    }, [enabled]);
}
