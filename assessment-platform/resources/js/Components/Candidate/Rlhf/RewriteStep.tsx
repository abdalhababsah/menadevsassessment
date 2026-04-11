import { FormEvent, useState } from 'react';
import { RightRailCard } from '@/Components/Candidate/Rlhf/RightRailCard';
import { StickyForm } from '@/Components/Candidate/Rlhf/StickyForm';

type Props = {
    selectedSide: 'a' | 'b';
    sourceText: string;
    initialRewrite: string;
    guidelines: string | null;
    busy: boolean;
    onSubmit: (rewrite: string) => Promise<void> | void;
};

export function RewriteStep({
    selectedSide,
    sourceText,
    initialRewrite,
    guidelines,
    busy,
    onSubmit,
}: Props) {
    const [rewrite, setRewrite] = useState(initialRewrite || sourceText);

    const handleSubmit = async (event: FormEvent) => {
        event.preventDefault();
        await onSubmit(rewrite);
    };

    return (
        <div className="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
            <form onSubmit={handleSubmit} className="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div className="rounded-2xl bg-slate-950 p-4 text-white">
                    <p className="text-xs uppercase tracking-[0.25em] text-emerald-300">
                        Selected response {selectedSide.toUpperCase()}
                    </p>
                    <p className="mt-3 whitespace-pre-wrap text-sm leading-7 text-slate-100">{sourceText}</p>
                </div>
                <textarea
                    value={rewrite}
                    onChange={(event) => setRewrite(event.target.value)}
                    rows={12}
                    className="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none"
                />
                <button
                    type="submit"
                    disabled={busy || rewrite.trim() === ''}
                    className="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:opacity-50"
                >
                    {busy ? 'Saving…' : 'Save Rewrite'}
                </button>
            </form>

            <StickyForm>
                <RightRailCard title="Rewrite guidance" subtitle="Keep the good parts, fix the weak parts.">
                    <p className="whitespace-pre-wrap text-sm text-slate-600">
                        {guidelines ?? 'Improve clarity, factuality, and usefulness while preserving the original intent.'}
                    </p>
                </RightRailCard>
            </StickyForm>
        </div>
    );
}
