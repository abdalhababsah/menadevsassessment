import axios from 'axios';
import { useEffect } from 'react';

type Options = {
    enabled: boolean;
};

/**
 * Detects tab switches and window blurs during the quiz and reports them to
 * the server as suspicious events.
 */
export function useTabSwitchDetection({ enabled }: Options): void {
    useEffect(() => {
        if (!enabled) {
            return;
        }

        const reportEvent = (eventType: 'tab_switch' | 'window_blur') => {
            void axios.post('/api/quiz/suspicious-event', {
                event_type: eventType,
                metadata: { timestamp: new Date().toISOString() },
            });
        };

        const handleVisibilityChange = () => {
            if (document.visibilityState === 'hidden') {
                reportEvent('tab_switch');
            }
        };

        const handleWindowBlur = () => {
            reportEvent('window_blur');
        };

        document.addEventListener('visibilitychange', handleVisibilityChange);
        window.addEventListener('blur', handleWindowBlur);

        return () => {
            document.removeEventListener('visibilitychange', handleVisibilityChange);
            window.removeEventListener('blur', handleWindowBlur);
        };
    }, [enabled]);
}
