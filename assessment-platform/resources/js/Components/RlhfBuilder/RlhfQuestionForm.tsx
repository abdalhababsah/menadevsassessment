import { useForm } from "@inertiajs/react";
import { Stack } from "../verdant/layout/stack";
import { Frame } from "../verdant/layout/frame";
import { Button } from "../ui/button";
import StemEditor from "../questionbuilder/stemeditor";
export default function RlhfQuestionForm({ action, submitUrl, initial, title }: any) {
  const { data, setData, post, put, processing, errors } = useForm({
    stem: initial?.stem || '',
    difficulty: initial?.difficulty || 'medium',
    points: initial?.points || 20,
  });
  return (
    <form onSubmit={(e) => { e.preventDefault(); action === 'create' ? post(submitUrl) : put(submitUrl); }}>
       <Stack gap="comfortable">
          <Frame surface="base"><Stack gap="cozy"><h3>{title}</h3><StemEditor value={data.stem} onChange={(v:string) => setData('stem', v)} error={errors.stem} /></Stack></Frame>
          <div className="flex justify-end gap-3"><Button variant="verdant" type="submit" disabled={processing}>Save</Button></div>
       </Stack>
    </form>
  );
}
