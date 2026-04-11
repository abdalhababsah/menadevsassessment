import { CodingQuestion } from '@/Components/Candidate/QuestionTypes/CodingQuestion';
import { MultiSelectQuestion } from '@/Components/Candidate/QuestionTypes/MultiSelectQuestion';
import { SingleSelectQuestion } from '@/Components/Candidate/QuestionTypes/SingleSelectQuestion';
import { useAutoSave } from '@/Hooks/useAutoSave';
import { useQuizTimer } from '@/Hooks/useQuizTimer';
import CandidateLayout from '@/Layouts/CandidateLayout';
import axios from 'axios';
import { Head } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

type RuntimeState = {
    attempt: {
        id: number;
        status: string;
        navigation_mode: string;
        started_at: string | null;
        submitted_at: string | null;
    };
    quiz: {
        id: number;
        title: string;
    };
    section: {
        id: number;
        title: string;
        position: number;
        total_sections: number;
        time_limit_seconds: number | null;
        remaining_seconds: number | null;
        question_count: number;
    };
    question: {
        id: number;
        version: number;
        type: string;
        stem: string;
        instructions: string | null;
        points: number;
        time_limit_seconds: number | null;
        remaining_seconds: number | null;
        options: { id: number; content: string; content_type: string; position: number }[];
        coding: {
            allowed_languages: string[];
            starter_code: Record<string, string>;
            time_limit_ms?: number;
            memory_limit_mb?: number;
        } | null;
    };
    answer: {
        id: number;
        status: string;
        selected_option_ids: number[];
        coding: { language: string; code: string } | null;
    } | null;
    timers: {
        quiz_remaining_seconds: number | null;
        section_remaining_seconds: number | null;
        question_remaining_seconds: number | null;
    };
    progress: {
        question_index: number;
        questions_in_section: number;
        global_question_index: number;
        total_questions: number;
    };
    navigation: {
        mode: string;
        can_go_previous: boolean;
        has_next_question: boolean;
        has_next_section: boolean;
        is_last_question_in_section: boolean;
        is_last_question: boolean;
    };
};

type SubmittedAttempt = {
    id: number;
    status: string;
    submitted_at: string | null;
    auto_score: string | number | null;
    final_score: string | number | null;
};

export default function Runner() {
    const [state, setState] = useState<RuntimeState | null>(null);
    const [loading, setLoading] = useState(true);
    const [actioning, setActioning] = useState(false);
    const [submittedAttempt, setSubmittedAttempt] = useState<SubmittedAttempt | null>(null);
    const questionEnteredAtRef = useRef<number>(Date.now());
    const actionLockRef = useRef(false);

    const fetchState = async () => {
        setLoading(true);

        try {
            const { data } = await axios.get<RuntimeState>('/quiz/current');
            setState(data);
        } catch {
            window.location.assign('/quiz/start');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        void fetchState();
    }, []);

    useEffect(() => {
        questionEnteredAtRef.current = Date.now();
    }, [state?.question.id]);

    useEffect(() => {
        if (state?.question.type === 'rlhf') {
            window.location.assign('/quiz/rlhf');
        }
    }, [state?.question.id, state?.question.type]);

    const { schedule, saving, flush } = useAutoSave(async (payload: Record<string, unknown>) => {
        const { data } = await axios.post<{ answer: RuntimeState['answer'] }>('/quiz/answer', payload);

        setState((previous) => {
            if (previous === null) {
                return previous;
            }

            return {
                ...previous,
                answer: data.answer,
            };
        });
    }, 450);

    const withNavigationLock = async (callback: () => Promise<void>) => {
        if (actionLockRef.current) {
            return;
        }

        actionLockRef.current = true;
        setActioning(true);

        try {
            await flush();
            await callback();
        } finally {
            actionLockRef.current = false;
            setActioning(false);
        }
    };

    const loadTransition = async (endpoint: string) => {
        const { data } = await axios.post<RuntimeState>(endpoint);
        setState(data);
    };

    const submitQuiz = async () => {
        await withNavigationLock(async () => {
            const { data } = await axios.post<{ submitted: boolean; attempt: SubmittedAttempt }>('/quiz/submit');

            if (data.submitted) {
                setSubmittedAttempt(data.attempt);
                setState(null);
            }
        });
    };

    const moveToNextQuestion = async () => {
        if (state === null) {
            return;
        }

        if (state.navigation.is_last_question) {
            await submitQuiz();

            return;
        }

        await withNavigationLock(async () => {
            await loadTransition('/quiz/next-question');
        });
    };

    const moveToPreviousQuestion = async () => {
        if (state === null || !state.navigation.can_go_previous) {
            return;
        }

        await withNavigationLock(async () => {
            await loadTransition('/quiz/previous-question');
        });
    };

    const moveToNextSection = async () => {
        if (state === null) {
            return;
        }

        if (!state.navigation.has_next_section) {
            await submitQuiz();

            return;
        }

        await withNavigationLock(async () => {
            await loadTransition('/quiz/next-section');
        });
    };

    const elapsedQuestionSeconds = () => Math.max(0, Math.round((Date.now() - questionEnteredAtRef.current) / 1000));

    const persistSelections = (optionIds: number[]) => {
        if (state === null) {
            return;
        }

        setState({
            ...state,
            answer: state.answer
                ? { ...state.answer, selected_option_ids: optionIds, status: 'answered' }
                : {
                      id: 0,
                      status: 'answered',
                      selected_option_ids: optionIds,
                      coding: null,
                  },
        });

        schedule({
            question_id: state.question.id,
            option_ids: optionIds,
            time_spent_seconds: elapsedQuestionSeconds(),
        });
    };

    const persistCodingAnswer = (payload: { code: string; language: string }) => {
        if (state === null) {
            return;
        }

        setState({
            ...state,
            answer: state.answer
                ? { ...state.answer, coding: payload, status: 'answered' }
                : {
                      id: 0,
                      status: 'answered',
                      selected_option_ids: [],
                      coding: payload,
                  },
        });

        schedule({
            question_id: state.question.id,
            code: payload.code,
            language: payload.language,
            time_spent_seconds: elapsedQuestionSeconds(),
        });
    };

    const timerState = useQuizTimer({
        quizSeconds: state?.timers.quiz_remaining_seconds ?? null,
        sectionSeconds: state?.timers.section_remaining_seconds ?? null,
        questionSeconds: state?.timers.question_remaining_seconds ?? null,
        onQuizExpire: () => {
            void submitQuiz();
        },
        onSectionExpire: () => {
            void moveToNextSection();
        },
        onQuestionExpire: () => {
            void moveToNextQuestion();
        },
    });

    if (loading) {
        return (
            <CandidateLayout>
                <Head title="Quiz" />
                <div className="mx-auto max-w-4xl py-20 text-center text-sm text-gray-600">Loading assessment…</div>
            </CandidateLayout>
        );
    }

    if (submittedAttempt !== null) {
        return (
            <CandidateLayout>
                <Head title="Assessment submitted" />
                <div className="mx-auto max-w-2xl rounded-3xl border border-emerald-200 bg-white p-8 shadow-sm">
                    <p className="text-xs font-semibold uppercase tracking-[0.3em] text-emerald-600">Submitted</p>
                    <h1 className="mt-3 text-3xl font-semibold text-gray-950">Your assessment has been submitted.</h1>
                    <p className="mt-3 text-sm text-gray-600">
                        Status: <span className="font-medium text-gray-900">{submittedAttempt.status}</span>
                    </p>
                    {submittedAttempt.final_score !== null && (
                        <p className="mt-2 text-sm text-gray-600">
                            Final score: <span className="font-medium text-gray-900">{submittedAttempt.final_score}</span>
                        </p>
                    )}
                    {submittedAttempt.final_score === null && submittedAttempt.auto_score !== null && (
                        <p className="mt-2 text-sm text-gray-600">
                            Auto score: <span className="font-medium text-gray-900">{submittedAttempt.auto_score}</span>
                        </p>
                    )}
                </div>
            </CandidateLayout>
        );
    }

    if (state === null) {
        return (
            <CandidateLayout>
                <Head title="Quiz" />
                <div className="mx-auto max-w-4xl py-20 text-center text-sm text-gray-600">
                    The assessment runner is unavailable right now.
                </div>
            </CandidateLayout>
        );
    }

    const nextLabel = state.navigation.is_last_question
        ? 'Submit'
        : state.navigation.is_last_question_in_section && state.navigation.has_next_section
          ? 'Next Section'
          : 'Next';

    return (
        <CandidateLayout>
            <Head title={state.quiz.title} />

            <div className="mx-auto max-w-5xl pb-28">
                <div className="sticky top-4 z-20 mb-6 overflow-hidden rounded-3xl border border-gray-200 bg-white/95 shadow-sm backdrop-blur">
                    <div className="grid gap-4 px-5 py-4 md:grid-cols-[1.7fr,1fr] md:px-6">
                        <div>
                            <p className="text-[11px] font-semibold uppercase tracking-[0.28em] text-gray-500">
                                Section {state.section.position} of {state.section.total_sections}
                            </p>
                            <h1 className="mt-2 text-xl font-semibold text-gray-950">{state.section.title}</h1>
                            <p className="mt-2 text-sm text-gray-600">
                                Q {state.progress.global_question_index} of {state.progress.total_questions} - Section
                                question {state.progress.question_index} of {state.progress.questions_in_section}
                            </p>
                        </div>

                        <div className="grid grid-cols-3 gap-3">
                            <TimerCard label="Quiz" value={timerState.quizRemaining} />
                            <TimerCard label="Section" value={timerState.sectionRemaining} />
                            <TimerCard label="Question" value={timerState.questionRemaining} />
                        </div>
                    </div>

                    <div className="flex items-center justify-between border-t border-gray-100 px-5 py-3 text-xs text-gray-500 md:px-6">
                        <span>{saving ? 'Saving your work…' : 'Answers are autosaved.'}</span>
                        <span>{state.attempt.navigation_mode === 'free' ? 'Free navigation' : 'Forward only'}</span>
                    </div>
                </div>

                <div className="rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm md:p-8">
                    {state.question.type === 'single_select' && (
                        <SingleSelectQuestion
                            stem={state.question.stem}
                            instructions={state.question.instructions}
                            options={state.question.options}
                            selectedOptionId={state.answer?.selected_option_ids?.[0]}
                            onChange={(optionId) => persistSelections([optionId])}
                        />
                    )}

                    {state.question.type === 'multi_select' && (
                        <MultiSelectQuestion
                            stem={state.question.stem}
                            instructions={state.question.instructions}
                            options={state.question.options}
                            selectedOptionIds={state.answer?.selected_option_ids ?? []}
                            onChange={persistSelections}
                        />
                    )}

                    {state.question.type === 'coding' && (
                        <CodingQuestion
                            stem={state.question.stem}
                            instructions={state.question.instructions}
                            config={state.question.coding}
                            value={{
                                code: state.answer?.coding?.code ?? '',
                                language:
                                    state.answer?.coding?.language ??
                                    state.question.coding?.allowed_languages?.[0] ??
                                    'python',
                            }}
                            onChange={persistCodingAnswer}
                        />
                    )}
                </div>

                <div className="fixed bottom-4 left-0 right-0 z-30 px-4">
                    <div className="mx-auto flex max-w-5xl items-center justify-between rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-lg shadow-gray-950/5">
                        <div className="text-xs text-gray-500">
                            {state.navigation.can_go_previous
                                ? 'You can revisit earlier questions in this quiz.'
                                : 'Once you continue, you stay on the forward path.'}
                        </div>

                        <div className="flex items-center gap-3">
                            {state.navigation.can_go_previous && (
                                <button
                                    type="button"
                                    onClick={() => {
                                        void moveToPreviousQuestion();
                                    }}
                                    disabled={actioning}
                                    className="rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:border-gray-400 hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Previous
                                </button>
                            )}

                            <button
                                type="button"
                                onClick={() => {
                                    void moveToNextQuestion();
                                }}
                                disabled={actioning}
                                className="rounded-xl bg-gray-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {actioning ? 'Working…' : nextLabel}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </CandidateLayout>
    );
}

function TimerCard({ label, value }: { label: string; value: number | null }) {
    return (
        <div className="rounded-2xl bg-gray-950 px-3 py-3 text-white">
            <p className="text-[10px] uppercase tracking-[0.24em] text-gray-400">{label}</p>
            <p className="mt-1 text-lg font-semibold">{formatSeconds(value)}</p>
        </div>
    );
}

function formatSeconds(seconds: number | null): string {
    if (seconds === null) {
        return '--:--';
    }

    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;

    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
}
