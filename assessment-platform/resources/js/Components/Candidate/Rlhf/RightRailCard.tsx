import { PropsWithChildren } from 'react';

type Props = PropsWithChildren<{
    title: string;
    subtitle?: string;
}>;

export function RightRailCard({ title, subtitle, children }: Props) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="border-b border-slate-100 pb-3">
                <h3 className="font-serif text-lg text-slate-950">{title}</h3>
                {subtitle && <p className="mt-1 text-sm text-slate-600">{subtitle}</p>}
            </div>
            <div className="pt-4">{children}</div>
        </div>
    );
}
