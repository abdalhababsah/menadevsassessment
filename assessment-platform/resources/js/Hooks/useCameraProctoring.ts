import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

type Options = {
    /** Set to true when the quiz requires camera access. */
    enabled: boolean;
    /** Snapshot interval in milliseconds. Defaults to 30 000 (30 s). */
    intervalMs?: number;
    /** Video element ref to show a live preview (optional). */
    videoRef?: React.RefObject<HTMLVideoElement | null>;
};

type CameraState = 'idle' | 'requesting' | 'active' | 'denied';

/**
 * Manages camera access and periodic snapshot uploads for proctoring.
 *
 * - Requests getUserMedia on mount when `enabled` is true.
 * - Attaches the stream to `videoRef` if provided.
 * - Takes a JPEG snapshot every `intervalMs` and POSTs it as multipart to
 *   `/api/quiz/camera-snapshot`.
 * - Records a `camera_denied` suspicious event if permission is refused.
 */
export function useCameraProctoring({
    enabled,
    intervalMs = 30_000,
    videoRef,
}: Options): CameraState {
    const [cameraState, setCameraState] = useState<CameraState>('idle');
    const streamRef = useRef<MediaStream | null>(null);
    const canvasRef = useRef<HTMLCanvasElement | null>(null);

    useEffect(() => {
        if (!enabled) {
            return;
        }

        let active = true;
        let intervalId: ReturnType<typeof setInterval> | null = null;

        const captureAndUpload = async () => {
            const stream = streamRef.current;
            const video = videoRef?.current;
            if (!stream || !video) {
                return;
            }

            if (!canvasRef.current) {
                canvasRef.current = document.createElement('canvas');
            }
            const canvas = canvasRef.current;
            canvas.width = video.videoWidth || 320;
            canvas.height = video.videoHeight || 240;

            const ctx = canvas.getContext('2d');
            if (!ctx) {
                return;
            }
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            await new Promise<void>((resolve) => {
                canvas.toBlob(
                    async (blob) => {
                        if (!blob) {
                            resolve();
                            return;
                        }
                        const form = new FormData();
                        form.append('snapshot', blob, 'snapshot.jpg');
                        try {
                            await axios.post('/api/quiz/camera-snapshot', form, {
                                headers: { 'Content-Type': 'multipart/form-data' },
                            });
                        } catch {
                            // Best-effort.
                        }
                        resolve();
                    },
                    'image/jpeg',
                    0.7,
                );
            });
        };

        const startCamera = async () => {
            setCameraState('requesting');
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                if (!active) {
                    stream.getTracks().forEach((t) => t.stop());
                    return;
                }
                streamRef.current = stream;
                if (videoRef?.current) {
                    videoRef.current.srcObject = stream;
                }
                setCameraState('active');
                intervalId = setInterval(() => {
                    void captureAndUpload();
                }, intervalMs);
            } catch {
                setCameraState('denied');
                void axios.post('/api/quiz/suspicious-event', {
                    event_type: 'camera_denied',
                    metadata: { timestamp: new Date().toISOString() },
                });
            }
        };

        void startCamera();

        return () => {
            active = false;
            if (intervalId !== null) {
                clearInterval(intervalId);
            }
            streamRef.current?.getTracks().forEach((t) => t.stop());
            streamRef.current = null;
        };
    }, [enabled, intervalMs, videoRef]);

    return cameraState;
}
