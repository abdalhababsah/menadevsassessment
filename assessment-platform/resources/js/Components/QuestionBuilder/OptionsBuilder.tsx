export interface QuestionOptionInput {
    content: string;
    content_type: 'text' | 'image' | 'audio';
    is_correct: boolean;
    position: number;
}

interface Props {
    options: QuestionOptionInput[];
    onChange: (options: QuestionOptionInput[]) => void;
    mode: 'single' | 'multi';
    error?: string;
}

export default function OptionsBuilder({ options, onChange, mode, error }: Props) {
    const reposition = (items: QuestionOptionInput[]) =>
        items.map((opt, i) => ({ ...opt, position: i }));

    const addOption = () => {
        onChange([
            ...options,
            { content: '', content_type: 'text', is_correct: false, position: options.length },
        ]);
    };

    const removeOption = (index: number) => {
        onChange(reposition(options.filter((_, i) => i !== index)));
    };

    const updateOption = (index: number, updates: Partial<QuestionOptionInput>) => {
        onChange(options.map((opt, i) => (i === index ? { ...opt, ...updates } : opt)));
    };

    const markCorrect = (index: number) => {
        if (mode === 'single') {
            onChange(options.map((opt, i) => ({ ...opt, is_correct: i === index })));
        } else {
            updateOption(index, { is_correct: !options[index].is_correct });
        }
    };

    const moveUp = (index: number) => {
        if (index === 0) return;
        const next = [...options];
        [next[index - 1], next[index]] = [next[index], next[index - 1]];
        onChange(reposition(next));
    };

    const moveDown = (index: number) => {
        if (index === options.length - 1) return;
        const next = [...options];
        [next[index], next[index + 1]] = [next[index + 1], next[index]];
        onChange(reposition(next));
    };

    return (
        <div>
            <div className="space-y-2">
                {options.map((opt, i) => (
                    <div key={i} className="flex items-center gap-2 rounded-lg border border-gray-200 p-3">
                        <input
                            type={mode === 'single' ? 'radio' : 'checkbox'}
                            name="option-correct"
                            checked={opt.is_correct}
                            onChange={() => markCorrect(i)}
                            className="text-indigo-600"
                            aria-label={`Mark option ${i + 1} as correct`}
                        />
                        <select
                            value={opt.content_type}
                            onChange={(e) => updateOption(i, { content_type: e.target.value as QuestionOptionInput['content_type'] })}
                            className="rounded-lg border-gray-300 text-sm"
                        >
                            <option value="text">Text</option>
                            <option value="image">Image URL</option>
                            <option value="audio">Audio URL</option>
                        </select>
                        <input
                            type="text"
                            value={opt.content}
                            onChange={(e) => updateOption(i, { content: e.target.value })}
                            className="flex-1 rounded-lg border-gray-300 text-sm"
                            placeholder={opt.content_type === 'text' ? 'Option text' : 'URL'}
                        />
                        <button type="button" onClick={() => moveUp(i)} disabled={i === 0} className="px-2 text-gray-500 disabled:opacity-30">↑</button>
                        <button type="button" onClick={() => moveDown(i)} disabled={i === options.length - 1} className="px-2 text-gray-500 disabled:opacity-30">↓</button>
                        <button type="button" onClick={() => removeOption(i)} className="px-2 text-red-500 hover:text-red-700">×</button>
                    </div>
                ))}
            </div>
            <button
                type="button"
                onClick={addOption}
                className="mt-3 rounded-lg border border-dashed border-gray-300 px-4 py-2 text-sm text-gray-600 hover:border-gray-400 hover:bg-gray-50"
            >
                + Add Option
            </button>
            {error && <p className="mt-2 text-sm text-red-600">{error}</p>}
        </div>
    );
}
