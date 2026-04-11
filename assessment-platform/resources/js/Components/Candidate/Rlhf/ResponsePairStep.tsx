import { RightRailCard } from '@/Components/Candidate/Rlhf/RightRailCard';
import { StickyForm } from '@/Components/Candidate/Rlhf/StickyForm';
import { RlhfTurn } from '@/types/rlhf';

type Props = {
    turn: RlhfTurn;
};

export function ResponsePairStep({ turn }: Props) {
    return (
        <div className="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
            <div className="grid gap-4 lg:grid-cols-2">
                <ResponseCard
                    title={`Response A · ${turn.model_a}`}
                    content={turn.response_a}
                    status={turn.generation.a.status}
                    error={turn.generation.a.error}
                />
                <ResponseCard
                    title={`Response B · ${turn.model_b}`}
                    content={turn.response_b}
                    status={turn.generation.b.status}
                    error={turn.generation.b.error}
                />
            </div>

            <StickyForm>
                <RightRailCard
                    title="Generation"
                    subtitle="Both model outputs will appear here as soon as generation completes."
                >
                    <div className="space-y-3 text-sm text-slate-600">
                        <p>The page polls every 1.5 seconds until both responses are ready.</p>
                        <p>Status A: <span className="font-medium text-slate-900">{turn.generation.a.status}</span></p>
                        <p>Status B: <span className="font-medium text-slate-900">{turn.generation.b.status}</span></p>
                    </div>
                </RightRailCard>
            </StickyForm>
        </div>
    );
}

function ResponseCard({
    title,
    content,
    status,
    error,
}: {
    title: string;
    content: string | null;
    status: string;
    error: string | null;
}) {
    return (
        <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-xs uppercase tracking-[0.25em] text-slate-500">{title}</p>
            {content ? (
                <div className="mt-4 whitespace-pre-wrap text-sm leading-7 text-slate-800">{content}</div>
            ) : (
                <div className="mt-4 rounded-2xl bg-slate-100 p-5 text-sm text-slate-500">
                    {status === 'failed' ? error ?? 'Generation failed.' : 'Generating response…'}
                </div>
            )}
        </article>
    );
}
