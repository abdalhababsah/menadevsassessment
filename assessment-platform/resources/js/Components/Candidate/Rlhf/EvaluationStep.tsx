import { FormEvent, useState } from 'react';
import { ButtonScale } from '@/Components/Candidate/Rlhf/ButtonScale';
import { RightRailCard } from '@/Components/Candidate/Rlhf/RightRailCard';
import { StickyForm } from '@/Components/Candidate/Rlhf/StickyForm';
import { RlhfCriterion, ResponseSide, RlhfTurnCounter } from '@/types/rlhf';

type EvaluationValue = {
    criterion_id: number;
    rating_value: string;
    justification: string | null;
};

type Props = {
    side: ResponseSide;
    responseText: string;
    criteria: RlhfCriterion[];
    initialValues: Record<string, EvaluationValue>;
    counter: RlhfTurnCounter;
    busy: boolean;
    onSubmit: (values: EvaluationValue[]) => Promise<void> | void;
};

export function EvaluationStep({
    side,
    responseText,
    criteria,
    initialValues,
    counter,
    busy,
    onSubmit,
}: Props) {
    const [values, setValues] = useState<Record<string, EvaluationValue>>(initialValues);

    const handleSubmit = async (event: FormEvent) => {
        event.preventDefault();
        await onSubmit(
            criteria.map((criterion) => ({
                criterion_id: criterion.id,
                rating_value: values[String(criterion.id)]?.rating_value ?? '',
                justification: values[String(criterion.id)]?.justification ?? null,
            })),
        );
    };

    return (
        <div className="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
            <article className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p className="text-xs uppercase tracking-[0.25em] text-slate-500">
                    Evaluate Response {side.toUpperCase()}
                </p>
                <div className="mt-4 whitespace-pre-wrap text-sm leading-7 text-slate-800">{responseText}</div>
            </article>

            <StickyForm>
                <RightRailCard
                    title={`Criteria · ${counter.completed}/${counter.total}`}
                    subtitle="Score the current response against every criterion."
                >
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {criteria.map((criterion) => {
                            const current = values[String(criterion.id)] ?? {
                                criterion_id: criterion.id,
                                rating_value: '',
                                justification: null,
                            };
                            const justificationRequired = criterion.justification_required_when.includes(current.rating_value);

                            return (
                                <div key={criterion.id} className="space-y-3">
                                    <div>
                                        <h4 className="text-sm font-semibold text-slate-900">{criterion.name}</h4>
                                        <p className="mt-1 text-sm text-slate-600">{criterion.description}</p>
                                    </div>
                                    <ButtonScale
                                        labels={criterion.scale_labels}
                                        value={current.rating_value}
                                        onChange={(nextValue) =>
                                            setValues((existing) => ({
                                                ...existing,
                                                [criterion.id]: {
                                                    ...current,
                                                    rating_value: nextValue,
                                                },
                                            }))
                                        }
                                    />
                                    {(justificationRequired || current.justification) && (
                                        <textarea
                                            value={current.justification ?? ''}
                                            onChange={(event) =>
                                                setValues((existing) => ({
                                                    ...existing,
                                                    [criterion.id]: {
                                                        ...current,
                                                        justification: event.target.value,
                                                    },
                                                }))
                                            }
                                            rows={3}
                                            className="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none"
                                            placeholder="Add a short justification"
                                        />
                                    )}
                                </div>
                            );
                        })}

                        <button
                            type="submit"
                            disabled={busy}
                            className="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:opacity-50"
                        >
                            {busy ? 'Saving…' : `Save Response ${side.toUpperCase()} Scores`}
                        </button>
                    </form>
                </RightRailCard>
            </StickyForm>
        </div>
    );
}
