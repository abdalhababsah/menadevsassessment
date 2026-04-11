import { FormEvent, useState } from 'react';
import { RightRailCard } from '@/Components/Candidate/Rlhf/RightRailCard';
import { StickyForm } from '@/Components/Candidate/Rlhf/StickyForm';

type Props = {
    initialValue: string;
    guidelines: string | null;
    candidateInputMode: string;
    busy: boolean;
    onSubmit: (input: string) => Promise<void> | void;
};

export function PromptInputStep({ initialValue, guidelines, candidateInputMode, busy, onSubmit }: Props) {
    const [value, setValue] = useState(initialValue);

    const handleSubmit = async (event: FormEvent) => {
        event.preventDefault();
        await onSubmit(value);
    };

    return (
        <div className="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
            <form onSubmit={handleSubmit} className="rounded-2xl bg-slate-950 p-6 text-white shadow-lg">
                <p className="text-xs uppercase tracking-[0.3em] text-emerald-300">Your Prompt</p>
                <textarea
                    value={value}
                    onChange={(event) => setValue(event.target.value)}
                    rows={10}
                    className="mt-4 w-full rounded-2xl border border-white/10 bg-white/5 p-4 text-base text-white placeholder:text-slate-400 focus:border-emerald-400 focus:outline-none"
                    placeholder="Write the next prompt or message you want the model to answer."
                />
                <div className="mt-4 flex items-center justify-between gap-4">
                    <p className="text-sm text-slate-300">
                        Input mode: <span className="font-medium text-white">{candidateInputMode}</span>
                    </p>
                    <button
                        type="submit"
                        disabled={busy || value.trim() === ''}
                        className="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300 disabled:opacity-50"
                    >
                        {busy ? 'Sending…' : 'Generate Responses'}
                    </button>
                </div>
            </form>

            <StickyForm>
                <RightRailCard title="Guidelines" subtitle="Keep your prompt grounded, clear, and testable.">
                    <div className="space-y-3 text-sm text-slate-700">
                        {guidelines ? (
                            <p className="whitespace-pre-wrap">{guidelines}</p>
                        ) : (
                            <p>No extra guidelines were provided for this question.</p>
                        )}
                    </div>
                </RightRailCard>
            </StickyForm>
        </div>
    );
}
