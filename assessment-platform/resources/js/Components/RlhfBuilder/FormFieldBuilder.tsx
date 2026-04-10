export type FieldStage = 'pre_prompt' | 'post_prompt' | 'post_rewrite';
export type FieldType = 'radio' | 'multi_select' | 'text' | 'textarea' | 'dropdown';

export interface FormFieldInput {
    stage: FieldStage;
    field_key: string;
    label: string;
    description: string | null;
    field_type: FieldType;
    options: string[] | null;
    required: boolean;
    min_length: number | null;
    position: number;
}

interface Props {
    stage: FieldStage;
    fields: FormFieldInput[];
    onChange: (fields: FormFieldInput[]) => void;
    errors?: Record<string, string | undefined>;
}

const FIELD_TYPES: { value: FieldType; label: string }[] = [
    { value: 'radio', label: 'Radio (single choice)' },
    { value: 'multi_select', label: 'Multi-select (checkboxes)' },
    { value: 'dropdown', label: 'Dropdown' },
    { value: 'text', label: 'Text (single line)' },
    { value: 'textarea', label: 'Textarea' },
];

const NEEDS_OPTIONS: FieldType[] = ['radio', 'multi_select', 'dropdown'];

export default function FormFieldBuilder({ stage, fields, onChange, errors = {} }: Props) {
    const stageFields = fields.filter((f) => f.stage === stage);

    const reposition = (items: FormFieldInput[]) => items.map((f, i) => ({ ...f, position: i }));

    const addField = () => {
        const newField: FormFieldInput = {
            stage,
            field_key: '',
            label: '',
            description: null,
            field_type: 'radio',
            options: ['Option 1', 'Option 2'],
            required: true,
            min_length: null,
            position: stageFields.length,
        };
        onChange([...fields, newField]);
    };

    const updateField = (globalIndex: number, updates: Partial<FormFieldInput>) => {
        onChange(fields.map((f, i) => (i === globalIndex ? { ...f, ...updates } : f)));
    };

    const removeField = (globalIndex: number) => {
        const next = fields.filter((_, i) => i !== globalIndex);
        const repositioned = next.map((f) => {
            if (f.stage !== stage) return f;
            const sameStage = next.filter((x) => x.stage === stage);
            return { ...f, position: sameStage.indexOf(f) };
        });
        onChange(repositioned);
    };

    const moveField = (globalIndex: number, direction: 1 | -1) => {
        const sibling = fields.findIndex((f, i) => {
            if (f.stage !== stage || i === globalIndex) return false;
            return direction === -1 ? i < globalIndex : i > globalIndex;
        });
        const targetIndex = direction === -1
            ? [...fields].map((f, i) => (f.stage === stage && i < globalIndex ? i : -1)).filter((x) => x !== -1).pop()
            : fields.findIndex((f, i) => f.stage === stage && i > globalIndex);

        if (targetIndex === undefined || targetIndex === -1) return;
        if (sibling === -1) return;

        const next = [...fields];
        [next[globalIndex], next[targetIndex]] = [next[targetIndex], next[globalIndex]];
        onChange(reposition(next));
    };

    const updateOption = (globalIndex: number, optionIndex: number, value: string) => {
        const field = fields[globalIndex];
        const opts = [...(field.options ?? [])];
        opts[optionIndex] = value;
        updateField(globalIndex, { options: opts });
    };

    const addOption = (globalIndex: number) => {
        const field = fields[globalIndex];
        const opts = [...(field.options ?? []), `Option ${(field.options?.length ?? 0) + 1}`];
        updateField(globalIndex, { options: opts });
    };

    const removeOption = (globalIndex: number, optionIndex: number) => {
        const field = fields[globalIndex];
        const opts = (field.options ?? []).filter((_, i) => i !== optionIndex);
        updateField(globalIndex, { options: opts });
    };

    return (
        <div className="space-y-3">
            {stageFields.length === 0 && (
                <p className="text-xs text-gray-500">No fields yet for this stage.</p>
            )}
            {stageFields.map((field) => {
                const globalIndex = fields.indexOf(field);
                const stageSiblings = fields.filter((f) => f.stage === stage);
                const stageIndex = stageSiblings.indexOf(field);

                return (
                    <div key={`${stage}-${globalIndex}`} className="rounded-lg border border-gray-200 p-3">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-semibold text-gray-500">Field {stageIndex + 1}</span>
                            <div className="flex items-center gap-1">
                                <button type="button" onClick={() => moveField(globalIndex, -1)} disabled={stageIndex === 0} className="px-2 text-gray-500 disabled:opacity-30">↑</button>
                                <button type="button" onClick={() => moveField(globalIndex, 1)} disabled={stageIndex === stageSiblings.length - 1} className="px-2 text-gray-500 disabled:opacity-30">↓</button>
                                <button type="button" onClick={() => removeField(globalIndex)} className="px-2 text-red-500 hover:text-red-700">×</button>
                            </div>
                        </div>

                        <div className="mt-2 grid grid-cols-2 gap-2">
                            <div>
                                <label className="block text-xs font-medium text-gray-700">Field Key</label>
                                <input
                                    type="text"
                                    value={field.field_key}
                                    onChange={(e) => updateField(globalIndex, { field_key: e.target.value })}
                                    className="mt-1 block w-full rounded-lg border-gray-300 font-mono text-xs"
                                    placeholder="snake_case_key"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-700">Label</label>
                                <input
                                    type="text"
                                    value={field.label}
                                    onChange={(e) => updateField(globalIndex, { label: e.target.value })}
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-xs"
                                />
                            </div>
                        </div>

                        <div className="mt-2">
                            <label className="block text-xs font-medium text-gray-700">Description (optional)</label>
                            <input
                                type="text"
                                value={field.description ?? ''}
                                onChange={(e) => updateField(globalIndex, { description: e.target.value || null })}
                                className="mt-1 block w-full rounded-lg border-gray-300 text-xs"
                            />
                        </div>

                        <div className="mt-2 grid grid-cols-2 gap-2">
                            <div>
                                <label className="block text-xs font-medium text-gray-700">Type</label>
                                <select
                                    value={field.field_type}
                                    onChange={(e) => {
                                        const newType = e.target.value as FieldType;
                                        const updates: Partial<FormFieldInput> = { field_type: newType };
                                        if (NEEDS_OPTIONS.includes(newType) && !field.options) {
                                            updates.options = ['Option 1', 'Option 2'];
                                        }
                                        if (!NEEDS_OPTIONS.includes(newType)) {
                                            updates.options = null;
                                        }
                                        updateField(globalIndex, updates);
                                    }}
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-xs"
                                >
                                    {FIELD_TYPES.map((t) => <option key={t.value} value={t.value}>{t.label}</option>)}
                                </select>
                            </div>
                            <div className="flex items-end gap-3">
                                <label className="flex items-center gap-2 text-xs text-gray-700">
                                    <input
                                        type="checkbox"
                                        checked={field.required}
                                        onChange={(e) => updateField(globalIndex, { required: e.target.checked })}
                                        className="rounded border-gray-300 text-indigo-600"
                                    />
                                    Required
                                </label>
                                {(field.field_type === 'text' || field.field_type === 'textarea') && (
                                    <label className="flex items-center gap-1 text-xs text-gray-700">
                                        Min length:
                                        <input
                                            type="number"
                                            value={field.min_length ?? ''}
                                            onChange={(e) => updateField(globalIndex, { min_length: e.target.value ? parseInt(e.target.value, 10) : null })}
                                            min={0}
                                            className="w-16 rounded-lg border-gray-300 text-xs"
                                        />
                                    </label>
                                )}
                            </div>
                        </div>

                        {NEEDS_OPTIONS.includes(field.field_type) && (
                            <div className="mt-2">
                                <label className="block text-xs font-medium text-gray-700">Options</label>
                                <div className="mt-1 space-y-1">
                                    {(field.options ?? []).map((opt, oi) => (
                                        <div key={oi} className="flex items-center gap-2">
                                            <input
                                                type="text"
                                                value={opt}
                                                onChange={(e) => updateOption(globalIndex, oi, e.target.value)}
                                                className="flex-1 rounded-lg border-gray-300 text-xs"
                                            />
                                            <button type="button" onClick={() => removeOption(globalIndex, oi)} className="text-red-500 hover:text-red-700">×</button>
                                        </div>
                                    ))}
                                    <button
                                        type="button"
                                        onClick={() => addOption(globalIndex)}
                                        className="text-xs text-indigo-600 hover:text-indigo-800"
                                    >
                                        + Add option
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                );
            })}
            <button
                type="button"
                onClick={addField}
                className="w-full rounded-lg border border-dashed border-gray-300 px-3 py-2 text-xs text-gray-600 hover:border-gray-400 hover:bg-gray-50"
            >
                + Add Field
            </button>
            {errors.form_fields && <p className="mt-2 text-sm text-red-600">{errors.form_fields}</p>}
        </div>
    );
}
