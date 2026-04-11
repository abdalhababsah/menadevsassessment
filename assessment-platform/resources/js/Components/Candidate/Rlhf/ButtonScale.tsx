type Props = {
    labels: Record<string, string>;
    value: string | null;
    onChange: (value: string) => void;
};

export function ButtonScale({ labels, value, onChange }: Props) {
    return (
        <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
            {Object.entries(labels).map(([key, label]) => {
                const active = value === key;

                return (
                    <button
                        key={key}
                        type="button"
                        onClick={() => onChange(key)}
                        className={`rounded-xl border px-3 py-3 text-left transition ${
                            active
                                ? 'border-emerald-500 bg-emerald-50 text-emerald-950'
                                : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'
                        }`}
                    >
                        <span className="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                            {key}
                        </span>
                        <span className="mt-1 block text-sm">{label}</span>
                    </button>
                );
            })}
        </div>
    );
}
