import CandidateLayout from '@/Layouts/CandidateLayout';
import { CodingQuestion } from '@/Components/Candidate/QuestionTypes/CodingQuestion';
import { MultiSelectQuestion } from '@/Components/Candidate/QuestionTypes/MultiSelectQuestion';
import { SingleSelectQuestion } from '@/Components/Candidate/QuestionTypes/SingleSelectQuestion';
import { useAutoSave } from '@/Hooks/useAutoSave';
import { useQuizTimer } from '@/Hooks/useQuizTimer';
import axios from 'axios';
import { Head } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

type RuntimeState = {
    attempt: {
        id: number;
        status: string;
        navigation_mode: string;
        time_limit_seconds: number | null;
    };
    quiz: { id: number; title: string };
    section: {
        id: number;
        title: string;
        position: number;
        total_sections: number;
        time_limit_seconds: number | null;
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
    progress: {
        question_index: number;
        questions_in_section: number;
    };
};

export default function Runner() {
    const [state, setState] = useState<RuntimeState | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);

    const fetchState = async () => {
        setLoading(true);
        const { data } = await axios.get('/quiz/current');
        setState(data);
        setLoading(false);
    };

    useEffect(() => {
        fetchState().catch(() => setLoading(false));
    }, []);

    const { schedule: scheduleSave, saving } = useAutoSave(async (payload: any) => {
        await axios.post('/quiz/answer', payload);
    }, 500);

    const timerState = useQuizTimer({
        quizSeconds: state?.attempt.time_limit_seconds ?? null,
        sectionSeconds: state?.section.time_limit_seconds ?? null,
        questionSeconds: state?.question.time_limit_seconds ?? null,
        onQuizExpire: () => submitQuiz(),
        onQuestionExpire: () => nextQuestion(),
    });

    const onSelectionChange = (optionIds: number[]) => {
        if (!state) return;
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
        scheduleSave({
            question_id: state.question.id,
            option_ids: optionIds,
        });
    };

    const onCodingChange = (payload: { code: string; language: string }) => {
        if (!state) return;
        setState({
            ...state,
            answer: state.answer
                ? { ...state.answer, coding: payload, status: 'answered' }
                : { id: 0, status: 'answered', selected_option_ids: [], coding: payload },
        });
        scheduleSave({
            question_id: state.question.id,
            code: payload.code,
            language: payload.language,
        });
    };

    const nextQuestion = async () => {
        const { data } = await axios.post('/quiz/next-question');
        setState(data);
    };

    const submitQuiz = async () => {
        if (submitting) return;
        setSubmitting(true);
        const { data } = await axios.post('/quiz/submit');
        if (data.submitted) {
            window.location.href = '/';
        } else {
            setSubmitting(false);
        }
    };

    const isLastQuestion = useMemo(() => {
        if (!state) return false;
        const onLastSection = state.section.position === state.section.total_sections;
        const onLastQuestion = state.progress.question_index === state.progress.questions_in_section;
        return onLastSection && onLastQuestion;
    }, [state]);

    if (loading || !state) {
        return (
            <CandidateLayout>
                <Head title="Quiz" />
                <div className="mx-auto max-w-3xl py-12 text-center text-gray-600">Loading quiz…</div>
            </CandidateLayout>
        );
    }

    return (
        <CandidateLayout>
            <Head title={state.quiz.title} />

            <div className="mx-auto max-w-5xl space-y-6 py-6">
                <div className="flex flex-wrap items-center justify-between gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                    <div>
                        <p className="text-xs uppercase tracking-wide text-gray-500">
                            Section {state.section.position} of {state.section.total_sections}
                        </p>
                        <h2 className="text-lg font-semibold text-gray-900">{state.section.title}</h2>
                        <p className="text-xs text-gray-500">
                            Question {state.progress.question_index} of {state.progress.questions_in_section}
                        </p>
                    </div>
                    <div className="flex items-center gap-6 text-sm font-medium text-gray-900">
                        {state.attempt.time_limit_seconds !== null && (
                            <div>
                                <p className="text-xs uppercase tracking-wide text-gray-500">Quiz Timer</p>
                                <p>{formatSeconds(timerState.quizRemaining)}</p>
                            </div>
                        )}
                        {state.section.time_limit_seconds !== null && (
                            <div>
                                <p className="text-xs uppercase tracking-wide text-gray-500">Section Timer</p>
                                <p>{formatSeconds(timerState.sectionRemaining)}</p>
                            </div>
                        )}
                        <div className="flex items-center gap-2 text-xs text-gray-500">
                            {saving ? 'Saving…' : 'Saved'}
                        </div>
                    </div>
                </div>

                <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    {state.question.type === 'single_select' && (
                        <SingleSelectQuestion
                            stem={state.question.stem}
                            instructions={state.question.instructions}
                            options={state.question.options}
                            selectedOptionId={state.answer?.selected_option_ids?.[0]}
                            onChange={(id) => onSelectionChange([id])}
                        />
                    )}
                    {state.question.type === 'multi_select' && (
                        <MultiSelectQuestion
                            stem={state.question.stem}
                            instructions={state.question.instructions}
                            options={state.question.options}
                            selectedOptionIds={state.answer?.selected_option_ids ?? []}
                            onChange={(ids) => onSelectionChange(ids)}
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
                            onChange={onCodingChange}
                        />
                    )}
                </div>

                <div className="flex justify-end gap-3">
                    {!isLastQuestion && (
                        <button
                            type="button"
                            onClick={nextQuestion}
                            className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500"
                        >
                            Next
                        </button>
                    )}
                    {isLastQuestion && (
                        <button
                            type="button"
                            onClick={submitQuiz}
                            disabled={submitting}
                            className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-500 disabled:opacity-50"
                        >
                            {submitting ? 'Submitting…' : 'Submit'}
                        </button>
                    )}
                </div>
            </div>
        </CandidateLayout>
    );
}

function formatSeconds(seconds: number | null): string {
    if (seconds === null) return '—';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}
