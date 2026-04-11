import { useForm } from "@inertiajs/react";
import { Stack } from "../verdant/layout/Stack";
import { Frame } from "../verdant/layout/Frame";
import { Button } from "../ui/button";
import StemEditor from "./StemEditor";
import { Field } from "../verdant/form/Field";

export default function RlhfQuestionForm({ action, submitUrl, tags, initial, title }: any) {
  const { data, setData, post, put, processing, errors } = useForm({
    stem: initial?.stem || '',
    difficulty: initial?.difficulty || 'medium',
    points: initial?.points || 20,
    tags: initial?.tags?.map((t: any) => t.id) || [],
    rlhf_config: initial?.rlhf_config || { type: 'sxs', criteria: [] },
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
          </Stack>
        </Frame>
        <div className="flex justify-end gap-3">
          <Button variant="verdant" type="submit" disabled={processing}>Save RLHF Question</Button>
        </div>
      </Stack>
    </form>
  );
}
