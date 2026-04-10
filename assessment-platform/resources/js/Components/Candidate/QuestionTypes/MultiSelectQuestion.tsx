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
    selectedOptionIds: number[];
    onChange: (ids: number[]) => void;
};

export function MultiSelectQuestion({ stem, instructions, options, selectedOptionIds, onChange }: Props) {
    const toggle = (id: number) => {
        if (selectedOptionIds.includes(id)) {
            onChange(selectedOptionIds.filter((x) => x !== id));
        } else {
            onChange([...selectedOptionIds, id]);
        }
    };

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
                        const active = selectedOptionIds.includes(option.id);
                        return (
                            <label
                                key={option.id}
                                className={`flex cursor-pointer items-start gap-3 rounded-lg border px-4 py-3 transition ${
                                    active
                                        ? 'border-indigo-500 bg-indigo-50 text-indigo-900'
                                        : 'border-gray-200 bg-white text-gray-900 hover:border-gray-300'
                                }`}
                            >
                                <input
                                    type="checkbox"
                                    className="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    checked={active}
                                    onChange={() => toggle(option.id)}
                                />
                                <span>{option.content}</span>
                            </label>
                        );
                    })}
            </div>
        </div>
    );
}
