import CandidateLayout from '@/Layouts/CandidateLayout';
import { EvaluationStep } from '@/Components/Candidate/Rlhf/EvaluationStep';
import { FormStep } from '@/Components/Candidate/Rlhf/FormStep';
import { PromptInputStep } from '@/Components/Candidate/Rlhf/PromptInputStep';
import { ResponsePairStep } from '@/Components/Candidate/Rlhf/ResponsePairStep';
import { RewriteStep } from '@/Components/Candidate/Rlhf/RewriteStep';
import { SxsRatingStep } from '@/Components/Candidate/Rlhf/SxsRatingStep';
import { TurnContainer } from '@/Components/Candidate/Rlhf/TurnContainer';
import { useGenerationPolling } from '@/Hooks/useGenerationPolling';
import { ResponseSide, RlhfState } from '@/types/rlhf';
import axios from 'axios';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

type Props = {
    state: RlhfState;
};

type SubmittedAttempt = {
    id: number;
    status: string;
    submitted_at: string | null;
    auto_score: string | number | null;
    final_score: string | number | null;
};

export default function Runner({ state }: Props) {
    const [runtimeState, setRuntimeState] = useState<RlhfState>(state);
    const [busyKey, setBusyKey] = useState<string | null>(null);
    const [submittedAttempt, setSubmittedAttempt] = useState<SubmittedAttempt | null>(null);

    useGenerationPolling({
        enabled: runtimeState.current_step === 'response_pair',
        onUpdate: setRuntimeState,
    });

    const currentTurn = runtimeState.current_turn;

    const send = async (key: string, url: string, payload: Record<string, unknown> = {}) => {
        setBusyKey(key);
        try {
            const { data } = await axios.post(url, payload);
            if (data.state) {
                setRuntimeState(data.state);
            }
        } finally {
            setBusyKey(null);
        }
    };

    const continueAssessment = async () => {
        setBusyKey('continue');

        try {
            const { data: current } = await axios.get('/quiz/current');

            if (current.navigation?.is_last_question) {
                const { data } = await axios.post<{
                    submitted: boolean;
                    exit_fullscreen: boolean;
                    redirect: string;
                    attempt: SubmittedAttempt;
                }>('/quiz/final-submit');

                if (data.submitted) {
                    if (data.exit_fullscreen && document.fullscreenElement !== null) {
                        try {
                            await document.exitFullscreen();
                        } catch {
                            // Ignore — best-effort.
                        }
                    }
                    window.location.assign(data.redirect);
                }

                return;
            }

            const { data: nextState } = await axios.post('/quiz/next-question');

            if (nextState.question?.type === 'rlhf') {
                window.location.assign('/quiz/rlhf');

                return;
            }

            window.location.assign('/quiz/run');
        } finally {
            setBusyKey(null);
        }
    };

    if (submittedAttempt !== null) {
        return (
            <CandidateLayout>
                <Head title="Assessment submitted" />

                <div className="mx-auto max-w-2xl rounded-[28px] border border-emerald-200 bg-white p-8 shadow-sm">
                    <p className="text-xs uppercase tracking-[0.3em] text-emerald-700">Submitted</p>
                    <h1 className="mt-3 font-serif text-3xl text-slate-950">Your assessment has been submitted.</h1>
                    <p className="mt-3 text-sm text-slate-600">
                        Status: <span className="font-semibold text-slate-900">{submittedAttempt.status}</span>
                    </p>
                    {submittedAttempt.final_score !== null && (
                        <p className="mt-2 text-sm text-slate-600">
                            Final score: <span className="font-semibold text-slate-900">{submittedAttempt.final_score}</span>
                        </p>
                    )}
                    {submittedAttempt.final_score === null && submittedAttempt.auto_score !== null && (
                        <p className="mt-2 text-sm text-slate-600">
                            Auto score: <span className="font-semibold text-slate-900">{submittedAttempt.auto_score}</span>
                        </p>
                    )}
                </div>
            </CandidateLayout>
        );
    }

    return (
        <CandidateLayout>
            <Head title={`RLHF · ${runtimeState.quiz.title}`} />

            <div className="space-y-6">
                <section className="rounded-[32px] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.12),_transparent_35%),linear-gradient(180deg,_#ffffff,_#f8fafc)] p-6 shadow-sm">
                    <div className="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p className="text-xs uppercase tracking-[0.3em] text-slate-500">RLHF Runner</p>
                            <h1 className="mt-2 font-serif text-3xl text-slate-950">{runtimeState.quiz.title}</h1>
                            <p className="mt-3 max-w-3xl whitespace-pre-wrap text-sm leading-7 text-slate-600">
                                {runtimeState.question.stem}
                            </p>
                        </div>
                        <div className="rounded-2xl bg-slate-950 px-4 py-3 text-right text-white">
                            <p className="text-xs uppercase tracking-[0.25em] text-emerald-300">Progress</p>
                            <p className="mt-2 text-lg font-semibold">
                                Turn {runtimeState.progress.current_turn ?? runtimeState.progress.completed_turns} of {runtimeState.progress.total_turns}
                            </p>
                            <p className="text-sm text-slate-300">{runtimeState.current_step.replaceAll('_', ' ')}</p>
                        </div>
                    </div>
                </section>

                {runtimeState.turns
                    .filter((turn) => currentTurn === null || turn.id !== currentTurn.id)
                    .map((turn) => (
                        <TurnContainer key={turn.id} turnNumber={turn.turn_number}>
                            <div className="space-y-4">
                                <div className="rounded-2xl bg-slate-100 p-4">
                                    <p className="text-xs uppercase tracking-[0.25em] text-slate-500">Candidate Prompt</p>
                                    <p className="mt-2 whitespace-pre-wrap text-sm leading-7 text-slate-700">{turn.candidate_input}</p>
                                </div>
                                <div className="grid gap-4 lg:grid-cols-2">
                                    <article className="rounded-2xl border border-slate-200 p-4">
                                        <p className="text-xs uppercase tracking-[0.25em] text-slate-500">Response A</p>
                                        <p className="mt-2 whitespace-pre-wrap text-sm leading-7 text-slate-700">{turn.response_a}</p>
                                    </article>
                                    <article className="rounded-2xl border border-slate-200 p-4">
                                        <p className="text-xs uppercase tracking-[0.25em] text-slate-500">Response B</p>
                                        <p className="mt-2 whitespace-pre-wrap text-sm leading-7 text-slate-700">{turn.response_b}</p>
                                    </article>
                                </div>
                            </div>
                        </TurnContainer>
                    ))}

                {currentTurn && (
                    <TurnContainer turnNumber={currentTurn.turn_number} current>
                        {runtimeState.current_step === 'pre_prompt_form' && (
                            <FormStep
                                title="Pre-prompt form"
                                description="Capture the setup details before the model sees your prompt."
                                fields={runtimeState.question.form_fields.pre_prompt}
                                initialValues={currentTurn.form_responses.pre_prompt}
                                counter={currentTurn.counters.pre_prompt}
                                busy={busyKey === 'pre_prompt'}
                                onSubmit={(values) => send('pre_prompt', '/quiz/rlhf/form', {
                                    stage: 'pre_prompt',
                                    responses: values,
                                })}
                            />
                        )}

                        {runtimeState.current_step === 'prompt_input' && (
                            <PromptInputStep
                                initialValue={currentTurn.candidate_input ?? ''}
                                guidelines={runtimeState.question.guidelines_markdown}
                                candidateInputMode={runtimeState.question.candidate_input_mode}
                                busy={busyKey === 'prompt'}
                                onSubmit={(input) => send('prompt', '/quiz/rlhf/prompt-input', { input })}
                            />
                        )}

                        {runtimeState.current_step === 'response_pair' && <ResponsePairStep turn={currentTurn} />}

                        {runtimeState.current_step === 'post_prompt_form' && (
                            <FormStep
                                title="Post-prompt form"
                                description="Capture your first-pass reaction before deeper scoring."
                                fields={runtimeState.question.form_fields.post_prompt}
                                initialValues={currentTurn.form_responses.post_prompt}
                                counter={currentTurn.counters.post_prompt}
                                busy={busyKey === 'post_prompt'}
                                onSubmit={(values) => send('post_prompt', '/quiz/rlhf/form', {
                                    stage: 'post_prompt',
                                    responses: values,
                                })}
                            />
                        )}

                        {runtimeState.current_step === 'evaluate_a' && currentTurn.response_a && (
                            <EvaluationStep
                                side={'a' satisfies ResponseSide}
                                responseText={currentTurn.response_a}
                                criteria={runtimeState.question.criteria}
                                initialValues={currentTurn.evaluations.a}
                                counter={currentTurn.counters.evaluate_a}
                                busy={busyKey === 'evaluate_a'}
                                onSubmit={(evaluations) => send('evaluate_a', '/quiz/rlhf/evaluation', {
                                    response_side: 'a',
                                    evaluations,
                                })}
                            />
                        )}

                        {runtimeState.current_step === 'evaluate_b' && currentTurn.response_b && (
                            <EvaluationStep
                                side={'b' satisfies ResponseSide}
                                responseText={currentTurn.response_b}
                                criteria={runtimeState.question.criteria}
                                initialValues={currentTurn.evaluations.b}
                                counter={currentTurn.counters.evaluate_b}
                                busy={busyKey === 'evaluate_b'}
                                onSubmit={(evaluations) => send('evaluate_b', '/quiz/rlhf/evaluation', {
                                    response_side: 'b',
                                    evaluations,
                                })}
                            />
                        )}

                        {runtimeState.current_step === 'sxs_rating' && currentTurn.response_a && currentTurn.response_b && (
                            <SxsRatingStep
                                responseA={currentTurn.response_a}
                                responseB={currentTurn.response_b}
                                initialRating={currentTurn.sxs_rating}
                                initialJustification={currentTurn.sxs_justification}
                                busy={busyKey === 'sxs'}
                                onSubmit={(rating, justification) => send('sxs', '/quiz/rlhf/sxs-rating', {
                                    rating,
                                    justification,
                                })}
                            />
                        )}

                        {runtimeState.current_step === 'rewrite' && currentTurn.selected_side && (
                            <RewriteStep
                                selectedSide={currentTurn.selected_side}
                                sourceText={currentTurn.selected_side === 'a' ? currentTurn.response_a ?? '' : currentTurn.response_b ?? ''}
                                initialRewrite={currentTurn.selected_response_rewrite ?? ''}
                                guidelines={runtimeState.question.guidelines_markdown}
                                busy={busyKey === 'rewrite'}
                                onSubmit={(rewrite) => send('rewrite', '/quiz/rlhf/rewrite', { rewrite })}
                            />
                        )}

                        {runtimeState.current_step === 'post_rewrite_form' && (
                            <FormStep
                                title="Post-rewrite form"
                                description="Record your final checks after editing the chosen response."
                                fields={runtimeState.question.form_fields.post_rewrite}
                                initialValues={currentTurn.form_responses.post_rewrite}
                                counter={currentTurn.counters.post_rewrite}
                                busy={busyKey === 'post_rewrite'}
                                onSubmit={(values) => send('post_rewrite', '/quiz/rlhf/form', {
                                    stage: 'post_rewrite',
                                    responses: values,
                                })}
                            />
                        )}

                        {runtimeState.current_step === 'turn_complete' && (
                            <div className="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
                                <div className="rounded-2xl bg-slate-950 p-6 text-white shadow-lg">
                                    <p className="text-xs uppercase tracking-[0.3em] text-emerald-300">Turn complete</p>
                                    <h3 className="mt-3 font-serif text-2xl">Ready for the next turn</h3>
                                    <p className="mt-3 max-w-2xl text-sm leading-7 text-slate-300">
                                        All required forms, evaluations, side-by-side rating, and rewrite work for this turn are saved.
                                    </p>
                                    <button
                                        type="button"
                                        onClick={() => send('advance_turn', '/quiz/rlhf/turn/advance')}
                                        disabled={busyKey === 'advance_turn'}
                                        className="mt-6 rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300 disabled:opacity-50"
                                    >
                                        {busyKey === 'advance_turn' ? 'Advancing…' : 'Advance Turn'}
                                    </button>
                                </div>
                            </div>
                        )}
                    </TurnContainer>
                )}

                {runtimeState.question_completed && (
                    <section className="rounded-[28px] border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
                        <p className="text-xs uppercase tracking-[0.3em] text-emerald-700">Question complete</p>
                        <h2 className="mt-2 font-serif text-2xl text-emerald-950">All RLHF turns have been finished.</h2>
                        <p className="mt-3 text-sm leading-7 text-emerald-900">
                            Your work for this RLHF question has been recorded successfully.
                        </p>
                        <button
                            type="button"
                            onClick={() => {
                                void continueAssessment();
                            }}
                            disabled={busyKey === 'continue'}
                            className="mt-6 rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:opacity-50"
                        >
                            {busyKey === 'continue' ? 'Continuing…' : 'Continue Assessment'}
                        </button>
                    </section>
                )}
            </div>
        </CandidateLayout>
    );
}
