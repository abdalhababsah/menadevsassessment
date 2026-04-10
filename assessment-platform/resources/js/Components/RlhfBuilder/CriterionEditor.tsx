import ScaleEditor, { ScaleType } from './ScaleEditor';

export interface CriterionInput {
    name: string;
    description: string;
    scale_type: ScaleType;
    scale_labels: string[];
    justification_required_when: number[];
    position: number;
}

interface Props {
    criteria: CriterionInput[];
    onChange: (criteria: CriterionInput[]) => void;
    errors?: Record<string, string | undefined>;
}

const DEFAULT_LABELS: Record<ScaleType, string[]> = {
    three_point_quality: ['Poor', 'Acceptable', 'Excellent'],
    five_point_centered: ['Much worse', 'Slightly worse', 'About the same', 'Slightly better', 'Much better'],
    five_point_satisfaction: ['Very dissatisfied', 'Dissatisfied', 'Neutral', 'Satisfied', 'Very satisfied'],
};

export default function CriterionEditor({ criteria, onChange, errors = {} }: Props) {
    const reposition = (items: CriterionInput[]) => items.map((c, i) => ({ ...c, position: i }));

    const addCriterion = () => {
        onChange([
            ...criteria,
            {
                name: '',
                description: '',
                scale_type: 'three_point_quality',
                scale_labels: DEFAULT_LABELS.three_point_quality,
                justification_required_when: [],
                position: criteria.length,
            },
        ]);
    };

    const removeCriterion = (index: number) => {
        onChange(reposition(criteria.filter((_, i) => i !== index)));
    };

    const updateCriterion = (index: number, updates: Partial<CriterionInput>) => {
        onChange(criteria.map((c, i) => (i === index ? { ...c, ...updates } : c)));
    };

    const moveUp = (index: number) => {
        if (index === 0) return;
        const next = [...criteria];
        [next[index - 1], next[index]] = [next[index], next[index - 1]];
        onChange(reposition(next));
    };

    const moveDown = (index: number) => {
        if (index === criteria.length - 1) return;
        const next = [...criteria];
        [next[index], next[index + 1]] = [next[index + 1], next[index]];
        onChange(reposition(next));
    };

    return (
        <div className="space-y-4">
            {criteria.map((criterion, i) => (
                <div key={i} className="rounded-lg border border-gray-200 p-4">
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Criterion {i + 1}
                        </span>
                        <div className="flex items-center gap-1">
                            <button type="button" onClick={() => moveUp(i)} disabled={i === 0} className="px-2 text-gray-500 disabled:opacity-30">↑</button>
                            <button type="button" onClick={() => moveDown(i)} disabled={i === criteria.length - 1} className="px-2 text-gray-500 disabled:opacity-30">↓</button>
                            <button type="button" onClick={() => removeCriterion(i)} className="px-2 text-red-500 hover:text-red-700">×</button>
                        </div>
                    </div>

                    <div className="mt-3 grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-medium text-gray-700">Name</label>
                            <input
                                type="text"
                                value={criterion.name}
                                onChange={(e) => updateCriterion(i, { name: e.target.value })}
                                className="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                                placeholder="e.g. Helpfulness"
                            />
                            {errors[`criteria.${i}.name`] && <p className="mt-1 text-xs text-red-600">{errors[`criteria.${i}.name`]}</p>}
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-700">Description (markdown)</label>
                            <textarea
                                value={criterion.description}
                                onChange={(e) => updateCriterion(i, { description: e.target.value })}
                                rows={2}
                                className="mt-1 block w-full rounded-lg border-gray-300 font-mono text-xs"
                                placeholder="What this criterion measures..."
                            />
                            {errors[`criteria.${i}.description`] && <p className="mt-1 text-xs text-red-600">{errors[`criteria.${i}.description`]}</p>}
                        </div>
                    </div>

                    <div className="mt-3">
                        <ScaleEditor
                            scaleType={criterion.scale_type}
                            scaleLabels={criterion.scale_labels}
                            onScaleTypeChange={(type) => updateCriterion(i, { scale_type: type, scale_labels: DEFAULT_LABELS[type], justification_required_when: [] })}
                            onScaleLabelsChange={(labels) => updateCriterion(i, { scale_labels: labels })}
                            justificationRequiredWhen={criterion.justification_required_when}
                            onJustificationChange={(values) => updateCriterion(i, { justification_required_when: values })}
                            error={errors[`criteria.${i}.scale_labels`]}
                        />
                    </div>
                </div>
            ))}
            <button
                type="button"
                onClick={addCriterion}
                className="w-full rounded-lg border border-dashed border-gray-300 px-4 py-3 text-sm text-gray-600 hover:border-gray-400 hover:bg-gray-50"
            >
                + Add Criterion
            </button>
            {errors.criteria && <p className="mt-2 text-sm text-red-600">{errors.criteria}</p>}
        </div>
    );
}
