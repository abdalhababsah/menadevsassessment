import { useForm } from "@inertiajs/react";
import { Stack } from "../verdant/layout/Stack";
import { Frame } from "../verdant/layout/Frame";
import { Button } from "../ui/button";
import { Input } from "../verdant/form/Input";
import { Select } from "../verdant/form/Select";
import { MultiSelect } from "../verdant/form/MultiSelect";
import StemEditor from "./StemEditor";
import { CodeInput } from "../verdant/form/CodeInput";
import { RichEditor } from "../verdant/form/RichEditor";
import { Field } from "../verdant/form/Field";

export default function CodingQuestionForm({ action, submitUrl, tags, initial, title }: any) {
  const { data, setData, post, put, processing, errors } = useForm({
    stem: initial?.stem || '',
    instructions: initial?.instructions || '',
    difficulty: initial?.difficulty || 'medium',
    points: initial?.points || 10,
    time_limit_seconds: initial?.time_limit_seconds || 600,
    tags: initial?.tags?.map((t: any) => t.id) || [],
    coding_config: initial?.coding_config || { language: 'javascript', starter_code: '', solution_code: '' },
    test_cases: initial?.test_cases || [{ input: '', output: '', is_public: true, points: 5 }],
  });

  const onSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (action === 'create') post(submitUrl);
    else put(submitUrl);
  };

  return (
    <form onSubmit={onSubmit}>
      <Stack gap="comfortable">
        <Frame surface="base" padding="comfortable" elevation={1}>
          <Stack gap="comfortable">
            <h3 className="font-display text-xl text-stone-900">{title}</h3>
            <StemEditor value={data.stem} onChange={(val: string) => setData('stem', val)} error={errors.stem} />
            <Field label="Instructions" description="Describe the coding challenge in detail">
               <RichEditor content={data.instructions} onChange={(val: string) => setData('instructions', val)} />
            </Field>
          </Stack>
        </Frame>

        <Frame surface="base" padding="comfortable" elevation={1}>
          <Stack gap="comfortable">
            <h3 className="font-display text-xl text-stone-900">Configuration</h3>
            <div className="grid grid-cols-2 gap-4">
              <Field label="Language">
                <Select 
                  value={data.coding_config.language} 
                  onChange={(val: string) => setData('coding_config', { ...data.coding_config, language: val })} 
                  options={[{label: 'JavaScript', value: 'javascript'}, {label: 'Python', value: 'python'}, {label: 'PHP', value: 'php'}]} 
                />
              </Field>
              <Field label="Time Limit (Seconds)">
                 <Input type="number" value={data.time_limit_seconds} onChange={(e) => setData('time_limit_seconds', e.target.value)} />
              </Field>
            </div>
            <Field label="Starter Code">
               <CodeInput value={data.coding_config.starter_code} onChange={(val:any) => setData('coding_config', { ...data.coding_config, starter_code: val || '' })} language={data.coding_config.language} />
            </Field>
          </Stack>
        </Frame>

        <div className="flex justify-end gap-3">
          <Button variant="verdant" type="submit" disabled={processing}>Save Question</Button>
        </div>
      </Stack>
    </form>
  );
}
