import { useForm } from "@inertiajs/react";
import { Stack } from "../verdant/layout/Stack";
import { Frame } from "../verdant/layout/Frame";
import { Button } from "../ui/button";
import { Input } from "../verdant/form/Input";
import { Select } from "../verdant/form/Select";
import { MultiSelect } from "../verdant/form/MultiSelect";
import StemEditor from "./StemEditor";
import { QuestionOptionInput } from "./OptionsBuilder";
import { Field } from "../verdant/form/Field";

export default function SelectQuestionForm({ action, submitUrl, tags, initial, title, type = 'single_select' }: any) {
  const { data, setData, post, put, processing, errors } = useForm({
    stem: initial?.stem || '',
    difficulty: initial?.difficulty || 'medium',
    points: initial?.points || 1,
    tags: initial?.tags?.map((t: any) => t.id) || [],
    options: initial?.options || [{ text: '', is_correct: false }, { text: '', is_correct: false }],
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
            
            <div className="grid grid-cols-2 gap-4">
              <Field label="Difficulty">
                <Select 
                  value={data.difficulty} 
                  onChange={(val: string) => setData('difficulty', val)} 
                  options={[{label: 'Easy', value: 'easy'}, {label: 'Medium', value: 'medium'}, {label: 'Hard', value: 'hard'}]} 
                />
              </Field>
              <Field label="Points">
                <Input type="number" value={data.points} onChange={(e) => setData('points', e.target.value)} />
              </Field>
            </div>
            
            <Field label="Tags">
              <MultiSelect 
                options={tags.map((t: any) => ({ label: t.name, value: String(t.id) }))} 
                selected={data.tags.map(String)} 
                onChange={(vals: string[]) => setData('tags', vals.map(Number))} 
              />
            </Field>
          </Stack>
        </Frame>

        <Frame surface="base" padding="comfortable" elevation={1}>
          <Stack gap="snug">
            <h3 className="font-display text-xl text-stone-900">Options</h3>
            <QuestionOptionInput 
               options={data.options} 
               onChange={(opts: any) => setData('options', opts)} 
               type={type === 'single_select' ? 'single' : 'multi'} 
            />
          </Stack>
        </Frame>

        <div className="flex justify-end gap-3">
          <Button variant="outline" type="button">Cancel</Button>
          <Button variant="verdant" type="submit" disabled={processing}>
            {action === 'create' ? 'Create' : 'Update'} Question
          </Button>
        </div>
      </Stack>
    </form>
  );
}
