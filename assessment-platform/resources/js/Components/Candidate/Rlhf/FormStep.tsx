import { FormEvent, useMemo, useState } from 'react';
import { RightRailCard } from '@/Components/Candidate/Rlhf/RightRailCard';
import { StickyForm } from '@/Components/Candidate/Rlhf/StickyForm';
import { RlhfFormField } from '@/types/rlhf';

type Props = {
    title: string;
    description: string;
    fields: RlhfFormField[];
    initialValues: Record<string, unknown>;
    counter: { completed: number; total: number };
    busy: boolean;
    onSubmit: (values: Record<string, unknown>) => Promise<void> | void;
};

export function FormStep({ title, description, fields, initialValues, counter, busy, onSubmit }: Props) {
    const [values, setValues] = useState<Record<string, unknown>>(initialValues);

    const sortedFields = useMemo(
        () => [...fields].sort((left, right) => left.position - right.position),
        [fields],
    );

    const handleSubmit = async (event: FormEvent) => {
        event.preventDefault();
        await onSubmit(values);
    };

    return (
        <div className="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
            <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p className="text-xs uppercase tracking-[0.25em] text-slate-500">{title}</p>
                <p className="mt-2 text-sm text-slate-600">{description}</p>
                <form onSubmit={handleSubmit} className="mt-6 space-y-5">
                    {sortedFields.map((field) => (
                        <div key={field.id} className="space-y-2">
                            <label className="block text-sm font-medium text-slate-900">{field.label}</label>
                            {field.description && <p className="text-sm text-slate-500">{field.description}</p>}
                            {renderField(field, values[field.field_key], (nextValue) =>
                                setValues((current) => ({ ...current, [field.field_key]: nextValue })),
                            )}
                        </div>
                    ))}

                    <button
                        type="submit"
                        disabled={busy}
                        className="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:opacity-50"
                    >
                        {busy ? 'Saving…' : 'Save and Continue'}
                    </button>
                </form>
            </div>

            <StickyForm>
                <RightRailCard
                    title="Completion"
                    subtitle={`${counter.completed}/${counter.total} responses saved for this step.`}
                >
                    <p className="text-sm text-slate-600">
                        Fill in every required field before moving to the next part of the turn.
                    </p>
                </RightRailCard>
            </StickyForm>
        </div>
    );
}

function renderField(
    field: RlhfFormField,
    value: unknown,
    onChange: (nextValue: unknown) => void,
) {
    const commonClassName =
        'w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none';

    if (field.field_type === 'textarea') {
        return (
            <textarea
                value={typeof value === 'string' ? value : ''}
                onChange={(event) => onChange(event.target.value)}
                rows={5}
                className={commonClassName}
            />
        );
    }

    if (field.field_type === 'text') {
        return (
            <input
                type="text"
                value={typeof value === 'string' ? value : ''}
                onChange={(event) => onChange(event.target.value)}
                className={commonClassName}
            />
        );
    }

    if (field.field_type === 'dropdown') {
        return (
            <select
                value={typeof value === 'string' ? value : ''}
                onChange={(event) => onChange(event.target.value)}
                className={commonClassName}
            >
                <option value="">Select an option</option>
                {(field.options ?? []).map((option) => (
                    <option key={option} value={option}>
                        {option}
                    </option>
                ))}
            </select>
        );
    }

    if (field.field_type === 'radio') {
        return (
            <div className="space-y-2">
                {(field.options ?? []).map((option) => (
                    <label key={option} className="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2">
                        <input
                            type="radio"
                            checked={value === option}
                            onChange={() => onChange(option)}
                        />
                        <span className="text-sm text-slate-700">{option}</span>
                    </label>
                ))}
            </div>
        );
    }

    if (field.field_type === 'multi_select') {
        const current = Array.isArray(value) ? value : [];

        return (
            <div className="space-y-2">
                {(field.options ?? []).map((option) => (
                    <label key={option} className="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2">
                        <input
                            type="checkbox"
                            checked={current.includes(option)}
                            onChange={(event) => {
                                if (event.target.checked) {
                                    onChange([...current, option]);
                                } else {
                                    onChange(current.filter((item) => item !== option));
                                }
                            }}
                        />
                        <span className="text-sm text-slate-700">{option}</span>
                    </label>
                ))}
            </div>
        );
    }

    return (
        <input
            type="text"
            value={typeof value === 'string' ? value : ''}
            onChange={(event) => onChange(event.target.value)}
            className={commonClassName}
        />
    );
}
