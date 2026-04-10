export interface CodingTestCaseInput {
    input: string;
    expected_output: string;
    is_hidden: boolean;
    weight: number;
}

interface Props {
    allowedLanguages: string[];
    onAllowedLanguagesChange: (langs: string[]) => void;
    starterCode: Record<string, string> | null;
    onStarterCodeChange: (code: Record<string, string>) => void;
    timeLimitMs: number;
    onTimeLimitChange: (ms: number) => void;
    memoryLimitMb: number;
    onMemoryLimitChange: (mb: number) => void;
    testCases: CodingTestCaseInput[];
    onTestCasesChange: (cases: CodingTestCaseInput[]) => void;
    errors?: Record<string, string | undefined>;
}

const AVAILABLE_LANGUAGES = ['python', 'javascript', 'typescript', 'java', 'cpp', 'go', 'rust', 'php'];

export default function CodingConfig({
    allowedLanguages,
    onAllowedLanguagesChange,
    starterCode,
    onStarterCodeChange,
    timeLimitMs,
    onTimeLimitChange,
    memoryLimitMb,
    onMemoryLimitChange,
    testCases,
    onTestCasesChange,
    errors = {},
}: Props) {
    const toggleLanguage = (lang: string) => {
        if (allowedLanguages.includes(lang)) {
            onAllowedLanguagesChange(allowedLanguages.filter((l) => l !== lang));
        } else {
            onAllowedLanguagesChange([...allowedLanguages, lang]);
        }
    };

    const updateStarterCode = (lang: string, code: string) => {
        onStarterCodeChange({ ...(starterCode ?? {}), [lang]: code });
    };

    const addTestCase = () => {
        onTestCasesChange([
            ...testCases,
            { input: '', expected_output: '', is_hidden: true, weight: 1 },
        ]);
    };

    const updateTestCase = (index: number, updates: Partial<CodingTestCaseInput>) => {
        onTestCasesChange(testCases.map((tc, i) => (i === index ? { ...tc, ...updates } : tc)));
    };

    const removeTestCase = (index: number) => {
        onTestCasesChange(testCases.filter((_, i) => i !== index));
    };

    return (
        <div className="space-y-6">
            <div>
                <h4 className="text-sm font-semibold text-gray-900">Allowed Languages</h4>
                <div className="mt-2 flex flex-wrap gap-2">
                    {AVAILABLE_LANGUAGES.map((lang) => (
                        <button
                            key={lang}
                            type="button"
                            onClick={() => toggleLanguage(lang)}
                            className={`rounded-full px-3 py-1 text-xs font-medium transition ${
                                allowedLanguages.includes(lang)
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            }`}
                        >
                            {lang}
                        </button>
                    ))}
                </div>
                {errors.allowed_languages && <p className="mt-1 text-sm text-red-600">{errors.allowed_languages}</p>}
            </div>

            {allowedLanguages.length > 0 && (
                <div>
                    <h4 className="text-sm font-semibold text-gray-900">Starter Code</h4>
                    <div className="mt-2 space-y-3">
                        {allowedLanguages.map((lang) => (
                            <div key={lang}>
                                <label className="text-xs font-medium text-gray-600">{lang}</label>
                                <textarea
                                    value={starterCode?.[lang] ?? ''}
                                    onChange={(e) => updateStarterCode(lang, e.target.value)}
                                    rows={4}
                                    className="mt-1 block w-full rounded-lg border-gray-300 font-mono text-xs shadow-sm"
                                    placeholder={`// Starter code for ${lang}`}
                                />
                            </div>
                        ))}
                    </div>
                </div>
            )}

            <div className="grid grid-cols-2 gap-4">
                <div>
                    <label className="text-sm font-semibold text-gray-900">Time Limit (ms)</label>
                    <input
                        type="number"
                        value={timeLimitMs}
                        onChange={(e) => onTimeLimitChange(parseInt(e.target.value, 10) || 0)}
                        min={100}
                        className="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
                    />
                </div>
                <div>
                    <label className="text-sm font-semibold text-gray-900">Memory Limit (MB)</label>
                    <input
                        type="number"
                        value={memoryLimitMb}
                        onChange={(e) => onMemoryLimitChange(parseInt(e.target.value, 10) || 0)}
                        min={16}
                        className="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
                    />
                </div>
            </div>

            <div>
                <div className="flex items-center justify-between">
                    <h4 className="text-sm font-semibold text-gray-900">Test Cases</h4>
                    <button
                        type="button"
                        onClick={addTestCase}
                        className="rounded-lg border border-dashed border-gray-300 px-3 py-1 text-xs text-gray-600 hover:border-gray-400"
                    >
                        + Add Test Case
                    </button>
                </div>
                <div className="mt-3 space-y-3">
                    {testCases.map((tc, i) => (
                        <div key={i} className="rounded-lg border border-gray-200 p-3">
                            <div className="flex items-center justify-between">
                                <span className="text-xs font-medium text-gray-700">Test Case {i + 1}</span>
                                <button type="button" onClick={() => removeTestCase(i)} className="text-red-500 hover:text-red-700">×</button>
                            </div>
                            <div className="mt-2 grid grid-cols-2 gap-3">
                                <div>
                                    <label className="text-xs text-gray-600">Input</label>
                                    <textarea
                                        value={tc.input}
                                        onChange={(e) => updateTestCase(i, { input: e.target.value })}
                                        rows={3}
                                        className="mt-1 block w-full rounded-lg border-gray-300 font-mono text-xs"
                                    />
                                </div>
                                <div>
                                    <label className="text-xs text-gray-600">Expected Output</label>
                                    <textarea
                                        value={tc.expected_output}
                                        onChange={(e) => updateTestCase(i, { expected_output: e.target.value })}
                                        rows={3}
                                        className="mt-1 block w-full rounded-lg border-gray-300 font-mono text-xs"
                                    />
                                </div>
                            </div>
                            <div className="mt-2 flex items-center gap-4">
                                <label className="flex items-center gap-2 text-xs text-gray-700">
                                    <input
                                        type="checkbox"
                                        checked={tc.is_hidden}
                                        onChange={(e) => updateTestCase(i, { is_hidden: e.target.checked })}
                                        className="rounded text-indigo-600"
                                    />
                                    Hidden from candidate
                                </label>
                                <label className="flex items-center gap-2 text-xs text-gray-700">
                                    Weight:
                                    <input
                                        type="number"
                                        value={tc.weight}
                                        onChange={(e) => updateTestCase(i, { weight: parseFloat(e.target.value) || 0 })}
                                        step="0.1"
                                        min={0}
                                        className="w-20 rounded-lg border-gray-300 text-xs"
                                    />
                                </label>
                            </div>
                        </div>
                    ))}
                </div>
                {errors.test_cases && <p className="mt-2 text-sm text-red-600">{errors.test_cases}</p>}
            </div>
        </div>
    );
}
