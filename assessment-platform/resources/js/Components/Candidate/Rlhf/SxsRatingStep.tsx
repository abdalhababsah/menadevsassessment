import { FormEvent, useState } from 'react';
import { ButtonScale } from '@/Components/Candidate/Rlhf/ButtonScale';
import { RightRailCard } from '@/Components/Candidate/Rlhf/RightRailCard';
import { StickyForm } from '@/Components/Candidate/Rlhf/StickyForm';

type Props = {
    responseA: string;
    responseB: string;
    initialRating: number | null;
    initialJustification: string | null;
    busy: boolean;
    onSubmit: (rating: number, justification: string) => Promise<void> | void;
};

const scaleLabels: Record<string, string> = {
    '1': 'A is much better',
    '2': 'A is better',
    '3': 'A is slightly better',
    '4': 'Tie',
    '5': 'B is slightly better',
    '6': 'B is better',
    '7': 'B is much better',
};

export function SxsRatingStep({
    responseA,
    responseB,
    initialRating,
    initialJustification,
    busy,
    onSubmit,
}: Props) {
    const [rating, setRating] = useState<string>(initialRating ? String(initialRating) : '');
    const [justification, setJustification] = useState(initialJustification ?? '');

    const handleSubmit = async (event: FormEvent) => {
        event.preventDefault();
        await onSubmit(Number(rating), justification);
    };

    return (
        <div className="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
            <div className="grid gap-4 lg:grid-cols-2">
                <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p className="text-xs uppercase tracking-[0.25em] text-slate-500">Response A</p>
                    <div className="mt-4 whitespace-pre-wrap text-sm leading-7 text-slate-800">{responseA}</div>
                </article>
                <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p className="text-xs uppercase tracking-[0.25em] text-slate-500">Response B</p>
                    <div className="mt-4 whitespace-pre-wrap text-sm leading-7 text-slate-800">{responseB}</div>
                </article>
            </div>

            <StickyForm>
                <RightRailCard title="Side-by-side rating" subtitle="Pick the stronger response and justify it.">
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <ButtonScale labels={scaleLabels} value={rating} onChange={setRating} />
                        <textarea
                            value={justification}
                            onChange={(event) => setJustification(event.target.value)}
                            rows={4}
                            className="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none"
                            placeholder="Explain your preference."
                        />
                        <button
                            type="submit"
                            disabled={busy || rating === ''}
                            className="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:opacity-50"
                        >
                            {busy ? 'Saving…' : 'Save Preference'}
                        </button>
                    </form>
                </RightRailCard>
            </StickyForm>
        </div>
    );
}
