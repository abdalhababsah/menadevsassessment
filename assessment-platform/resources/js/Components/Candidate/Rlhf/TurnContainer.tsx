import { PropsWithChildren } from 'react';

type Props = PropsWithChildren<{
    turnNumber: number;
    current?: boolean;
}>;

export function TurnContainer({ turnNumber, current = false, children }: Props) {
    return (
        <section
            className={`rounded-[28px] border px-5 py-6 shadow-sm transition lg:px-7 ${
                current
                    ? 'border-emerald-200 bg-white'
                    : 'border-slate-200/80 bg-white/80'
            }`}
        >
            <div className="mb-5 flex items-center gap-3">
                <div className="flex h-11 w-11 items-center justify-center rounded-full bg-slate-900 text-sm font-semibold text-white">
                    T{turnNumber}
                </div>
                <div>
                    <p className="text-xs uppercase tracking-[0.25em] text-slate-500">Conversation Turn</p>
                    <h2 className="font-serif text-xl text-slate-950">
                        {current ? `Turn ${turnNumber} in progress` : `Turn ${turnNumber}`}
                    </h2>
                </div>
            </div>
            {children}
        </section>
    );
}
