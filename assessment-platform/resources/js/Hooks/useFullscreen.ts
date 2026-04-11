import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

type Options = {
    enabled: boolean;
    onExitBlocked: () => void;
};

/**
 * Manages the Fullscreen API for the quiz runner.
 *
 * - Enters fullscreen on mount when `enabled` is true.
 * - Fires a `fullscreen_exit` suspicious event to the server whenever the
 *   candidate leaves fullscreen.
 * - Calls `onExitBlocked` so the UI can show the re-entry overlay.
 */
export function useFullscreen({ enabled, onExitBlocked }: Options): {
    isFullscreen: boolean;
    requestFullscreen: () => Promise<void>;
} {
    const [isFullscreen, setIsFullscreen] = useState(false);
    const reportingRef = useRef(false);

    const requestFullscreen = useCallback(async () => {
        try {
            await document.documentElement.requestFullscreen();
        } catch {
            // Browser may deny if not triggered by a user gesture; ignore.
        }
    }, []);

    useEffect(() => {
        if (!enabled) {
            return;
        }

        void requestFullscreen();

        const handleChange = async () => {
            const inFullscreen = document.fullscreenElement !== null;
            setIsFullscreen(inFullscreen);

            if (!inFullscreen && !reportingRef.current) {
                reportingRef.current = true;
                try {
                    await axios.post('/api/quiz/suspicious-event', {
                        event_type: 'fullscreen_exit',
                        metadata: { timestamp: new Date().toISOString() },
                    });
                } catch {
                    // Best-effort — don't surface network errors to the candidate.
                } finally {
                    reportingRef.current = false;
                }
                onExitBlocked();
            }
        };

        document.addEventListener('fullscreenchange', handleChange);
        return () => {
            document.removeEventListener('fullscreenchange', handleChange);
        };
    }, [enabled, onExitBlocked, requestFullscreen]);

    return { isFullscreen, requestFullscreen };
}
