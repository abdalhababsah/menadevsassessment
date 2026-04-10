interface Props {
    value: string;
    onChange: (value: string) => void;
    error?: string;
    placeholder?: string;
    rows?: number;
}

export default function StemEditor({ value, onChange, error, placeholder, rows = 6 }: Props) {
    return (
        <div>
            <textarea
                value={value}
                onChange={(e) => onChange(e.target.value)}
                rows={rows}
                className="block w-full rounded-lg border-gray-300 font-mono text-sm shadow-sm"
                placeholder={placeholder ?? 'Write your question stem... Markdown supported.'}
            />
            <p className="mt-1 text-xs text-gray-500">
                Markdown supported. Image and audio uploads will be added in a future update.
            </p>
            {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
        </div>
    );
}
