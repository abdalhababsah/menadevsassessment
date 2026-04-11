type Props = {
    /** Number of exits already recorded (including the current one). */
    exitCount: number;
    /** Maximum allowed exits before the attempt is auto-submitted. 0 = no limit. */
    maxExits: number;
    onReEnter: () => Promise<void>;
};

/**
 * Full-screen overlay shown whenever the candidate exits fullscreen mode.
 * Prompts them to re-enter and communicates the remaining exit budget.
 */
export function AntiCheatOverlay({ exitCount, maxExits, onReEnter }: Props) {
    const remaining = maxExits > 0 ? maxExits - exitCount : null;
    const isLastWarning = remaining !== null && remaining <= 1;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/90 backdrop-blur-sm">
            <div className="mx-4 w-full max-w-md rounded-3xl border border-red-200 bg-white p-8 shadow-2xl">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                        <svg
                            className="h-5 w-5 text-red-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            strokeWidth={2}
                            stroke="currentColor"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"
                            />
                        </svg>
                    </div>
                    <div>
                        <p className="text-[11px] font-semibold uppercase tracking-widest text-red-600">
                            Proctoring alert
                        </p>
                        <h2 className="mt-0.5 text-lg font-semibold text-gray-950">
                            You left fullscreen
                        </h2>
                    </div>
                </div>

                <p className="mt-4 text-sm leading-relaxed text-gray-600">
                    This assessment requires you to remain in fullscreen mode at all times.
                    Exiting fullscreen is recorded as a proctoring event.
                </p>

                {remaining !== null && (
                    <div
                        className={`mt-4 rounded-xl px-4 py-3 text-sm font-medium ${
                            isLastWarning
                                ? 'border border-red-200 bg-red-50 text-red-700'
                                : 'border border-amber-200 bg-amber-50 text-amber-700'
                        }`}
                    >
                        {remaining <= 0
                            ? 'Your attempt has been submitted due to repeated fullscreen exits.'
                            : `You have ${remaining} fullscreen exit${remaining === 1 ? '' : 's'} remaining before your attempt is automatically submitted.`}
                    </div>
                )}

                {(remaining === null || remaining > 0) && (
                    <button
                        type="button"
                        onClick={() => {
                            void onReEnter();
                        }}
                        className="mt-6 w-full rounded-xl bg-gray-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-800"
                    >
                        Return to fullscreen
                    </button>
                )}
            </div>
        </div>
    );
}
