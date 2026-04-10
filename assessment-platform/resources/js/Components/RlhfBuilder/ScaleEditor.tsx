export type ScaleType = 'three_point_quality' | 'five_point_centered' | 'five_point_satisfaction';

const SCALE_DEFAULTS: Record<ScaleType, string[]> = {
    three_point_quality: ['Poor', 'Acceptable', 'Excellent'],
    five_point_centered: ['Much worse', 'Slightly worse', 'About the same', 'Slightly better', 'Much better'],
    five_point_satisfaction: ['Very dissatisfied', 'Dissatisfied', 'Neutral', 'Satisfied', 'Very satisfied'],
};

export const SCALE_LENGTHS: Record<ScaleType, number> = {
    three_point_quality: 3,
    five_point_centered: 5,
    five_point_satisfaction: 5,
};

interface Props {
    scaleType: ScaleType;
    scaleLabels: string[];
    onScaleTypeChange: (type: ScaleType) => void;
    onScaleLabelsChange: (labels: string[]) => void;
    justificationRequiredWhen: number[];
    onJustificationChange: (values: number[]) => void;
    error?: string;
}

export default function ScaleEditor({
    scaleType,
    scaleLabels,
    onScaleTypeChange,
    onScaleLabelsChange,
    justificationRequiredWhen,
    onJustificationChange,
    error,
}: Props) {
    const handleScaleTypeChange = (newType: ScaleType) => {
        onScaleTypeChange(newType);
        onScaleLabelsChange(SCALE_DEFAULTS[newType]);
        onJustificationChange([]);
    };

    const updateLabel = (index: number, value: string) => {
        const next = [...scaleLabels];
        next[index] = value;
        onScaleLabelsChange(next);
    };

    const toggleJustification = (value: number) => {
        if (justificationRequiredWhen.includes(value)) {
            onJustificationChange(justificationRequiredWhen.filter((v) => v !== value));
        } else {
            onJustificationChange([...justificationRequiredWhen, value].sort((a, b) => a - b));
        }
    };

    return (
        <div className="space-y-3">
            <div>
                <label className="block text-xs font-medium text-gray-700">Scale Type</label>
                <select
                    value={scaleType}
                    onChange={(e) => handleScaleTypeChange(e.target.value as ScaleType)}
                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                >
                    <option value="three_point_quality">3-point quality</option>
                    <option value="five_point_centered">5-point centered</option>
                    <option value="five_point_satisfaction">5-point satisfaction</option>
                </select>
            </div>

            <div>
                <label className="block text-xs font-medium text-gray-700">Scale Labels</label>
                <div className="mt-1 space-y-2">
                    {scaleLabels.map((label, i) => {
                        const value = i + 1;
                        const requiresJustification = justificationRequiredWhen.includes(value);

                        return (
                            <div key={i} className="flex items-center gap-2">
                                <span className="w-6 text-xs text-gray-500">{value}.</span>
                                <input
                                    type="text"
                                    value={label}
                                    onChange={(e) => updateLabel(i, e.target.value)}
                                    className="flex-1 rounded-lg border-gray-300 text-sm"
                                />
                                <label className="flex items-center gap-1 text-xs text-gray-600">
                                    <input
                                        type="checkbox"
                                        checked={requiresJustification}
                                        onChange={() => toggleJustification(value)}
                                        className="rounded border-gray-300 text-indigo-600"
                                    />
                                    Justify
                                </label>
                            </div>
                        );
                    })}
                </div>
                <p className="mt-1 text-xs text-gray-500">
                    Check &ldquo;Justify&rdquo; for ratings that require an explanation from the candidate.
                </p>
                {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
            </div>
        </div>
    );
}
