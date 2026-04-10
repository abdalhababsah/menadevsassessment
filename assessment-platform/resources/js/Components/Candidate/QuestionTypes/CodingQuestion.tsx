import React, { useEffect, useState } from 'react';

type CodingConfig = {
    allowed_languages: string[];
    starter_code: Record<string, string>;
    time_limit_ms?: number;
    memory_limit_mb?: number;
};

type Props = {
    stem: string;
    instructions?: string | null;
    config: CodingConfig | null;
    value: { code: string; language: string };
    onChange: (payload: { code: string; language: string }) => void;
};

export function CodingQuestion({ stem, instructions, config, value, onChange }: Props) {
    const [language, setLanguage] = useState(value.language || config?.allowed_languages?.[0] || 'python');
    const [code, setCode] = useState(value.code || '');

    useEffect(() => {
        onChange({ code, language });
    }, [code, language]);

    useEffect(() => {
        // Update local state when parent changes (e.g., initial load)
        setLanguage(value.language || config?.allowed_languages?.[0] || 'python');
        setCode(value.code || '');
    }, [value.language, value.code, config?.allowed_languages]);

    return (
        <div className="space-y-4">
            <div>
                <p className="text-lg font-semibold text-gray-900">{stem}</p>
                {instructions && <p className="mt-1 text-sm text-gray-600">{instructions}</p>}
            </div>

            <div className="flex items-center gap-3">
                <label className="text-xs uppercase tracking-wide text-gray-500">Language</label>
                <select
                    value={language}
                    onChange={(e) => setLanguage(e.target.value)}
                    className="rounded-md border border-gray-300 px-3 py-1 text-sm"
                >
                    {(config?.allowed_languages ?? ['python', 'javascript']).map((lang) => (
                        <option key={lang} value={lang}>
                            {lang}
                        </option>
                    ))}
                </select>
                {config?.time_limit_ms && (
                    <span className="text-xs text-gray-500">Time limit: {config.time_limit_ms} ms</span>
                )}
                {config?.memory_limit_mb && (
                    <span className="text-xs text-gray-500">Memory: {config.memory_limit_mb} MB</span>
                )}
            </div>

            <textarea
                value={code}
                onChange={(e) => setCode(e.target.value)}
                rows={12}
                className="w-full rounded-lg border border-gray-300 bg-gray-50 font-mono text-sm text-gray-900 shadow-inner focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Write your solution here…"
            />
        </div>
    );
}
