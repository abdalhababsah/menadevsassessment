import CandidateLayout from '@/Layouts/CandidateLayout';
import axios from 'axios';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from "sonner";

interface Props {
    candidate: {
        name: string | null;
        email: string;
    };
    quiz: {
        id: number;
        title: string;
        description: string | null;
        time_limit_seconds: number | null;
        navigation_mode: string;
        camera_enabled: boolean;
        anti_cheat_enabled: boolean;
        max_fullscreen_exits: number;
        passing_score: number | null;
    };
    invitation_token: string;
}

function formatDuration(seconds: number | null): string {
    if (seconds === null) return 'No time limit';
    const minutes = Math.floor(seconds / 60);
    const remaining = seconds % 60;
    return remaining === 0 ? `${minutes} minutes` : `${minutes}m ${remaining}s`;
}

export default function PreQuiz({ candidate, quiz, invitation_token }: Props) {
    const [cameraGranted, setCameraGranted] = useState(!quiz.camera_enabled);
    const [requestingCamera, setRequestingCamera] = useState(false);
    const [starting, setStarting] = useState(false);

    const requestCamera = async () => {
        setRequestingCamera(true);
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            stream.getTracks().forEach((t) => t.stop());
            setCameraGranted(true);
            toast.success("Camera access granted");
        } catch {
            setCameraGranted(false);
            toast.error("Camera access is required to take this assessment.");
        } finally {
            setRequestingCamera(false);
        }
    };

    const startQuiz = async () => {
        setStarting(true);

        try {
            const { data } = await axios.post('/quiz/start', { invitation_token });
            window.location.assign(data.run_url);
        } catch {
            setStarting(false);
            toast.error("The assessment could not be started. Refresh the page and try again.");
        }
    };


    const canStart = cameraGranted;

    return (
        <CandidateLayout>
            <Head title={quiz.title} />

            <div className="mx-auto max-w-2xl">
                <div className="rounded-xl bg-white p-8 shadow-sm ring-1 ring-gray-200">
                    <h1 className="text-2xl font-bold text-gray-900">{quiz.title}</h1>
                    {quiz.description && (
                        <p className="mt-2 text-sm text-gray-600">{quiz.description}</p>
                    )}

                    <div className="mt-6 grid grid-cols-2 gap-4 rounded-lg bg-gray-50 p-4">
                        <div>
                            <p className="text-xs font-medium uppercase tracking-wide text-gray-500">Time limit</p>
                            <p className="mt-1 text-sm text-gray-900">{formatDuration(quiz.time_limit_seconds)}</p>
                        </div>
                        <div>
                            <p className="text-xs font-medium uppercase tracking-wide text-gray-500">Navigation</p>
                            <p className="mt-1 text-sm text-gray-900">
                                {quiz.navigation_mode === 'forward_only' ? 'Forward only' : 'Free'}
                            </p>
                        </div>
                        {quiz.passing_score !== null && (
                            <div>
                                <p className="text-xs font-medium uppercase tracking-wide text-gray-500">Passing score</p>
                                <p className="mt-1 text-sm text-gray-900">{quiz.passing_score}%</p>
                            </div>
                        )}
                        <div>
                            <p className="text-xs font-medium uppercase tracking-wide text-gray-500">Signed in as</p>
                            <p className="mt-1 text-sm text-gray-900">{candidate.name ?? candidate.email}</p>
                        </div>
                    </div>

                    <div className="mt-6">
                        <h2 className="text-sm font-semibold text-gray-900">Rules</h2>
                        <ul className="mt-2 space-y-2 text-sm text-gray-700">
                            {quiz.navigation_mode === 'forward_only' && (
                                <li className="flex items-start gap-2">
                                    <span className="mt-1 h-1.5 w-1.5 flex-none rounded-full bg-amber-500" />
                                    Once you move to the next question, you cannot go back.
                                </li>
                            )}
                            {quiz.time_limit_seconds !== null && (
                                <li className="flex items-start gap-2">
                                    <span className="mt-1 h-1.5 w-1.5 flex-none rounded-full bg-amber-500" />
                                    The quiz will auto-submit when time runs out.
                                </li>
                            )}
                            {quiz.anti_cheat_enabled && (
                                <li className="flex items-start gap-2">
                                    <span className="mt-1 h-1.5 w-1.5 flex-none rounded-full bg-red-500" />
                                    Tab switching, copy/paste, and right-click are tracked. Up to{' '}
                                    {quiz.max_fullscreen_exits} fullscreen exits are allowed.
                                </li>
                            )}
                            {quiz.camera_enabled && (
                                <li className="flex items-start gap-2">
                                    <span className="mt-1 h-1.5 w-1.5 flex-none rounded-full bg-red-500" />
                                    Your webcam will capture periodic snapshots throughout the assessment.
                                </li>
                            )}
                            <li className="flex items-start gap-2">
                                <span className="mt-1 h-1.5 w-1.5 flex-none rounded-full bg-gray-400" />
                                Make sure you have a stable internet connection.
                            </li>
                        </ul>
                    </div>

                    {quiz.camera_enabled && !cameraGranted && (
                        <div className="mt-6 rounded-lg border border-amber-300 bg-amber-50 p-4">
                            <p className="text-sm font-medium text-amber-900">Camera permission required</p>
                            <p className="mt-1 text-xs text-amber-800">
                                Click below to grant camera access. You can revoke it after the assessment ends.
                            </p>
                            <button
                                type="button"
                                onClick={requestCamera}
                                disabled={requestingCamera}
                                className="mt-3 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-500 disabled:opacity-50"
                            >
                                {requestingCamera ? 'Requesting…' : 'Enable Camera'}
                            </button>
                        </div>
                    )}

                    <div className="mt-8 flex items-center justify-end">
                        <button
                            type="button"
                            onClick={startQuiz}
                            disabled={!canStart || starting}
                            className="rounded-lg bg-indigo-600 px-6 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
                        >
                            {starting ? 'Starting…' : 'Start Quiz'}
                        </button>
                    </div>
                </div>
            </div>
        </CandidateLayout>
    );
}
