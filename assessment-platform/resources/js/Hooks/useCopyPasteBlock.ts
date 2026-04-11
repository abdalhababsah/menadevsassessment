import axios from 'axios';
import { useEffect } from 'react';

type Options = {
    /** Set to false for coding questions where copy-paste is intentionally allowed. */
    enabled: boolean;
};

/**
 * Disables copy, cut, paste, and right-click within the quiz runner and
 * reports each attempt to the server.
 */
export function useCopyPasteBlock({ enabled }: Options): void {
    useEffect(() => {
        if (!enabled) {
            return;
        }

        const reportEvent = (eventType: 'copy_attempt' | 'paste_attempt' | 'right_click') => {
            void axios.post('/api/quiz/suspicious-event', {
                event_type: eventType,
                metadata: { timestamp: new Date().toISOString() },
            });
        };

        const block = (e: Event) => e.preventDefault();

        const handleCopy = (e: ClipboardEvent) => {
            block(e);
            reportEvent('copy_attempt');
        };

        const handleCut = (e: ClipboardEvent) => {
            block(e);
            reportEvent('copy_attempt');
        };

        const handlePaste = (e: ClipboardEvent) => {
            block(e);
            reportEvent('paste_attempt');
        };

        const handleContextMenu = (e: MouseEvent) => {
            block(e);
            reportEvent('right_click');
        };

        document.addEventListener('copy', handleCopy);
        document.addEventListener('cut', handleCut);
        document.addEventListener('paste', handlePaste);
        document.addEventListener('contextmenu', handleContextMenu);

        return () => {
            document.removeEventListener('copy', handleCopy);
            document.removeEventListener('cut', handleCut);
            document.removeEventListener('paste', handlePaste);
            document.removeEventListener('contextmenu', handleContextMenu);
        };
    }, [enabled]);
}
