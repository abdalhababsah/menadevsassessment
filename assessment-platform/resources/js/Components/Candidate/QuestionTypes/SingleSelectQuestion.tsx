import React from 'react';

type Option = {
    id: number;
    content: string;
    content_type: string;
    position: number;
};

type Props = {
    stem: string;
    instructions?: string | null;
    options: Option[];
    selectedOptionId?: number | null;
    onChange: (id: number) => void;
};

export function SingleSelectQuestion({ stem, instructions, options, selectedOptionId, onChange }: Props) {
    return (
        <div className="space-y-4">
            <div>
                <p className="text-lg font-semibold text-gray-900">{stem}</p>
                {instructions && <p className="mt-1 text-sm text-gray-600">{instructions}</p>}
            </div>
            <div className="space-y-2">
                {options
                    .slice()
                    .sort((a, b) => a.position - b.position)
                    .map((option) => {
                        const active = selectedOptionId === option.id;
                        return (
                            <button
                                key={option.id}
                                type="button"
                                onClick={() => onChange(option.id)}
                                className={`w-full rounded-lg border px-4 py-3 text-left transition ${
                                    active
                                        ? 'border-indigo-500 bg-indigo-50 text-indigo-900'
                                        : 'border-gray-200 bg-white text-gray-900 hover:border-gray-300'
                                }`}
                            >
                                {option.content}
                            </button>
                        );
                    })}
            </div>
        </div>
    );
}
